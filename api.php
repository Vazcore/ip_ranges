<?php

if (!isset($_POST['action']))
{
	echo json_encode(array("status"=>0, "description" => "Unknown request!"));
	return false;
}

$action = $_POST['action'];

if (!function_exists($action))
{
	echo json_encode(array("status"=>0, "description" => "Unknown method!"));
	return false;
}
else
{
	$action();
}

function to_sort()
{
	if (!isset($_POST["content"]))
	{
		echo json_encode(array("status"=>0, "description" => "Content is required!"));
		return false;
	}
	
	$content = $_POST["content"];
	
	// splice by new line delimiter
	$items = explode("\n", $content);
	
	$separated_items = array();
	// find ranges
	$separated_items = separate_ranges($items);
	
	// sort single ips
	$ips = array();
	if (count($separated_items["ips"]))
	{
		$ips = $separated_items["ips"];
		natsort($ips);		
	}
	
	//sort ranges of ip
	$ranges = array();
	if (count($separated_items["ranges"]))
	{
		$ranges = $separated_items["ranges"];
		natsort($ranges);
		$ranges = format_ranges($ranges);
	}
	
	// single ips which are not in any range list yet
	$ips_not_in_range = array();
	if (count($ranges) && count($ips))
	{		
		//iterate throw single ips to determine range accessory
		foreach($ips as $k => $ip)
		{
			if (!is_in_range($ranges, $ip))
			{
				$ips_not_in_range[] = $ip;
			}
		}
	}	
	
	if (count($ips) && !count($ranges))
	{
		$ips_not_in_range = $ips;
	}
	
	// mix single ips into ranges
	$merged_ranges = merge_into_ranges($ips_not_in_range);
	
	// combine all ips ranges
	$result_ips = array_merge($ranges, $merged_ranges);
	
	// final sort
	function cmp($a, $b)
	{
		if ($a[0] == $b[0])
		{
			return 0;
		}
		return ($a[0] < $b[0]) ? -1 : 1;
	}
	usort($result_ips, "cmp");
	
	header("Content-type: application/json; charset=utf-8");
	
	echo json_encode(array("status"=>0, "data" => $result_ips));
}

function merge_into_ranges($ips)
{
	$ranges = array();
	$Grouplist='';
	foreach($ips as $ip){ 
		   $ip2long=ip2long($ip);
		   if(is_array($Grouplist))
		    {
				$is_group=false;
				foreach($Grouplist as $Key=>$Range)
				{
					$Range=explode("/",$Range);
					if (($Range[0]-1)<$ip2long and $ip2long<($Range[1]+1))
					{
						$is_group=true;
						continue;
					}
					elseif (($Range[0]-1)==$ip2long)
					{
						$Grouplist[$Key]=$ip2long.'/'.$Range[1];
						$is_group=true;
					}
					elseif (($Range[1]+1)==$ip2long)
					{
						$Grouplist[$Key]=$Range[0].'/'.$ip2long;
						$is_group=true;
					}
				}
				if (!$is_group)
				{
					$Grouplist[]=($ip2long).'/'.($ip2long);
				}
		    }
			else
			{
				$Grouplist[]=($ip2long).'/'.($ip2long);
		    }
	}
	
	foreach($Grouplist as $v)
	{
		$r = explode("/",$v);
		$ranges[] = array(long2ip($r[0]), long2ip($r[1]));
	}
	return $ranges;
}

function is_in_range($ranges, $ip)
{
	foreach($ranges as $k => $range)
	{
		$from = sprintf("%u", ip2long($range[0]));
		$to = sprintf("%u", ip2long($range[1]));		
		$f_ip = sprintf("%u", ip2long($ip));		
		if ($f_ip >= $from && $f_ip <= $to)
		{			
			return true;
		}
	}
	return false;
}

function format_ranges($ranges)
{
	$resp_ranges = array();
	foreach($ranges as $k => $range)
	{
		$parts = explode('-', $range);
		sort($parts);
		$resp_ranges[] = $parts;
	}
	return $resp_ranges;
}

function separate_ranges($items)
{
	$ranges = array();
	$ips = array();
	foreach($items as $k => $item)
	{
		if (strstr($item, '-') !== false)
		{
			$range = explode("-", $item);
			if (isset($range[0]) && isset($range[1]))
			{
				if (filter_var($range[0], FILTER_VALIDATE_IP) && filter_var($range[1], FILTER_VALIDATE_IP))
				{
					$ranges[] = $item;
				}
			}			
		}
		elseif (strstr($item, '/') !== false)
		{
			$range = explode("/", $item);
			if (isset($range[0]) && isset($range[1]))
			{
				$to = intval(trim($range[1]));								
				if (filter_var($range[0], FILTER_VALIDATE_IP))
				{					
					$from_parts = explode(".", $range[0]);
					$from_parts[count($from_parts) - 1] = $to;
					$to_range = implode(".", $from_parts);
					
					if (filter_var($to_range, FILTER_VALIDATE_IP))
					{
						$ranges[] = $range[0] . "-" . $to_range;
					}					
				}
			}			
		}
		else
		{
			$item = trim($item);
			if (filter_var($item, FILTER_VALIDATE_IP))
			{
				$ips[] = $item;
			}
			
		}
	}	
	return array('ips'=>$ips, 'ranges'=>$ranges);
}

?>