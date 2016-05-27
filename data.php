<?php
session_start();

if(!isset($_SESSION['token']))
{
	header('Location: refresh.php');
}

$mapping = array('V2' => 'https://www.googleapis.com/analytics/v3/data/realtime?ids=ga%3A64110150&metrics=rt%3AactiveUsers&dimensions=rt%3Alatitude%2Crt%3Alongitude',
				'Online3' => 'https://www.googleapis.com/analytics/v3/data/realtime?ids=ga%3A53508797&metrics=rt%3AactiveUsers&dimensions=rt%3Alatitude%2Crt%3Alongitude');
$final = array();
foreach($mapping as $name => $url)
{
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_USERAGENT,'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13');
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
	'Authorization: ' . $_SESSION['token']
	));

	$result = json_decode(curl_exec($ch));
	curl_close($ch);

	$total = $result->totalsForAllResults->{'rt:activeUsers'};
	$rows = $result->rows;

	$fullData = array();
	foreach($rows as $r)
	{
		$rounded = array(round($r[0]),round($r[1]),round($r[2],2));

		if(array_key_exists($rounded[0] . '_' . $rounded[1], $fullData))
		{
			$fullData[$rounded[0] . '_' . $rounded[1]] += $rounded[2];
		}
		else
		{
			$fullData[$rounded[0] . '_' . $rounded[1]] = $rounded[2];
		}	
	}

	$ret = array();
	foreach($fullData as $k => $data)
	{
		$tab = split('_', $k);
		$ret[] = round($tab[0]);
		$ret[] = round($tab[1]);
		$ret[] = round(($data / $total), 3);
	}

	$final[] = array($name, $ret);
}

header('Content-Type: application/json');
exit(json_encode($final));