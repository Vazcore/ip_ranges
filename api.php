<?php

if (!isset($_POST['action']) && !isset($_GET['action']))
{
	echo json_encode(array("status"=>0, "description" => "Unknown request!"));
	return false;
}

if (isset($_POST["action"]))
	$action = $_POST['action'];

if (isset($_GET["action"]))
	$action = $_GET['action'];

if (!function_exists($action))
{
	echo json_encode(array("status"=>0, "description" => "Unknown method!"));
	return false;
}
else
{
	$action();
}


function download_file()
{	
	if (isset($_GET['filename']))
	{
		$filename = $_GET['filename'] . ".php";
	}
	else
	{
		$filename = "in.php";
	}
	if (ob_get_level()) {
      ob_end_clean();
    }
	header("Content-type: application/octet");
	header("Content-disposition: attachment; filename=".$filename);	
	readfile("buffer.txt");
	exit;
	
}

function save_to_file()
{
	if (!isset($_POST["content"]))
	{
		echo json_encode(array("status"=>0, "description" => "Content is required!"));
		return false;
	}
	
	$content = $_POST["content"];	
	if (isset($content["filename"]))
	{		
		file_put_contents("buffer.txt", $content["code"]);
		if (!is_dir("files"))
		{
			mkdir("files", 0777);
		}		
		if (!copy("buffer.txt", "files/" . $content["filename"] . ".php"))
		{
			echo json_encode(array("status"=>0, "description" => "Copy failed!"));
		}
		
	}
	
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
		
	// possible merge ranges
	$ranges = possible_merge_ranges($ranges);	
		
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

function possible_merge_ranges($ranges)
{	
	$merged_ranges = array();
	$marked_busy = array();
	$marked_busy_ids = array();
	
	foreach ($ranges as $k1 => $range)
	{
		if (in_array($k1, $marked_busy_ids))
		{
			continue;
		}
		foreach ($ranges as $k1_inner => $inner_range)
		{
			if (in_array($k1_inner, $marked_busy_ids) || $k1 == $k1_inner)
			{
				continue;
			}
			if (($bigger_range = in_range_each_other($range, $inner_range)) !== false)
			{				
				if (!isset($marked_busy[$k1]))
				{
					$marked_busy[$k1] = array();
				}
				$merged_ranges[] = $bigger_range;				
				$marked_busy[$k1][] = $k1_inner;
				$marked_busy_ids[] = $k1_inner;
				$marked_busy_ids[] = $k1;
			}
		}
	}
	
	foreach ($ranges as $k1 => $range)
	{
		if (!in_array($k1, $marked_busy_ids))
		{
			$merged_ranges[] = $range;
		}
	}
	
	return $merged_ranges;
}

function in_range_each_other($range, $inner_range)
{
	$range_ip_down = ip2long($range[0]);
	$range_ip_up = ip2long($range[1]);
	
	$range_ip_inner_down = ip2long($inner_range[0]);
	$range_ip_inner_up = ip2long($inner_range[1]);
	
	// down 1-st in range of 2-nd
	if ($range_ip_down >= $range_ip_inner_down && $range_ip_down <= $range_ip_inner_up)
	{
		// up 1-st in range of 2-nd
		if ($range_ip_up >= $range_ip_inner_down && $range_ip_up <= $range_ip_inner_up)
		{
			return $inner_range;
		}
	}
	
	// down 2-nd on range of 1-st
	if ($range_ip_inner_down >= $range_ip_down && $range_ip_inner_down <= $range_ip_up)
	{
		// up 2-nd in range of 1-st
		if ($range_ip_inner_up >= $range_ip_down && $range_ip_inner_up <= $range_ip_up)
		{			
			return $range;
		}
	}
	
	return false;
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
	
	if(is_array($Grouplist))
	{
		foreach($Grouplist as $v)
		{
			$r = explode("/",$v);
			$ranges[] = array(long2ip($r[0]), long2ip($r[1]));
		}
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
				if (filter_var($range[0], FILTER_VALIDATE_IP))
				{					
					$mask_range = cidrToRange(trim($item));					
					if (count($mask_range) && is_array($mask_range))
					{
						$ranges[] = $mask_range[0] . "-" . $mask_range[1];
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

function cidrToRange($cidr) {
  $range = array();
  $cidr = explode('/', $cidr);
  $range[0] = long2ip((ip2long($cidr[0])) & ((-1 << (32 - (int)$cidr[1]))));
  $range[1] = long2ip((ip2long($range[0])) + pow(2, (32 - (int)$cidr[1])) - 2);
  return $range;
}

?>