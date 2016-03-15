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
	
	
	echo json_encode(array("status"=>0, "data" => $ips_not_in_range));
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