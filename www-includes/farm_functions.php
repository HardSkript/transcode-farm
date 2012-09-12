<?php

/*

	FUNCTIONS THAT HAVE TO DO WITH FARMING
		cyle gage, emerson college, 2012
	
	
	getFarmingStatus($jid)
	getFarmingStatusByOutPath($path)
	addFarmingJob($jid, $paths, $options)
	
*/

require_once('dbconn_mongo.php');

// presets...
$tiers = array(
	'max' => array('vb' => 2200, 'vw' => 1920, 'vh' => 1080, 'ab' => 196),
	'ultra' => array('vb' => 1700, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'high' => array('vb' => 1200, 'vw' => 1280, 'vh' => 720, 'ab' => 128),
	'medium' => array('vb' => 600, 'vw' => 720, 'vh' => 480, 'ab' => 96),
	'small' => array('vb' => 300, 'vw' => 400, 'vh' => 260, 'ab' => 64)
);

function getFarmingStatus($jid = '') {
	// get full status of media being transcoded for this ID
	
	if (!isset($jid) || trim($jid) == '') {
		return false;
	}
	
	if (gettype($jid) == 'object' && get_class($jid) == 'MongoId') {
		$jid = $jid;
	} else if (gettype($jid) == 'string') {
		$jid = new MongoId($jid);
	} else {
		return false;
	}
	
	global $farmdb;
	
	$jobs = array();
	
	$find_jobs = $farmdb->jobs->find(array('jid' => $jid));
	if ($find_jobs->count() > 0) {
		foreach ($find_jobs as $job) {
			$jobs[] = $job;
		}
	}
	
	return $jobs;
	
}

function getFarmingStatusByOutPath($path = '') {
	// get the latest status of a particular farming job by its OUT path
	
	if (!isset($path) || trim($path) == '') {
		return false;
	}
	
	$path = trim($path);
	
	global $farmdb;
	$m->setSlaveOkay();
	
	$status = 'Unknown';
	
	$find_jobs = $farmdb->jobs->find(array('out' => $path));
	if ($find_jobs->count() == 0) {
		// no job found for that out path, whoops
		$status = 'No job found for that path.';
		return $status;
	} else if ($find_jobs->count() == 1) {
		$thejob = $find_jobs->getNext();
	} else {
		// sort by latest, take the first one
		$find_jobs->sort(array('tsu' => -1));
		$thejob = $find_jobs->getNext();
	}
	
	if (!isset($thejob['s']) || !is_numeric($thejob['s'])) {
		$status = 'No status found for that path.';
		return $status;
	}
	
	switch ($thejob['s']) {
		case 0:
		$status = 'Pending';
		break;
		case 1:
		$status = 'Transcoding';
		break;
		case 2:
		$status = 'Finished';
		break;
		case 3:
		$status = 'Error';
		break;
		default:
		$status = 'Unknown';
	}
	
	return $status;
}

function addFarmingJob($eid = '', $paths = array(), $options = '') {
	// add a new farming job for jid with options...
	// if options is a string, use that as a preset
	// if options is an array, use those explicit settings
	
	if (!isset($eid) || trim($eid) == '') {
		return false;
	}
	
	if (gettype($eid) == 'object' && get_class($eid) == 'MongoId') {
		$eid = $eid;
	} else if (gettype($eid) == 'string') {
		$eid = new MongoId($eid);
	} else {
		return false;
	}
	
	if (!isset($paths) || !is_array($paths)) {
		return false;
	}
	
	if (!isset($paths['in']) || !isset($paths['out'])) {
		return false;
	}
	
	global $tiers, $default_priority, $origin_id;
	
	$transcode_options = array();
	
	$tier_keys = array_keys($tiers);
	
	if (isset($options) && is_string($options) && in_array(strtolower($options), $tier_keys))  {
		// use the provided preset option
		$transcode_options = $tiers[strtolower($options)];
	} else if (isset($options) && is_array($options)) {
		$transcode_options = $options;
	} else {
		return false;
	}
	
	global $farmdb;
	
	$new_job = array();
	$new_job['eid'] = $eid; // the parent entry this farming job belongs to
	$new_job['p'] = $default_priority; // priority 100 for general jobs
	$new_job['o'] = $origin_id; // origin id #2 for general transcode farm
	$new_job['s'] = 0; // status of 0 for new jobs
	$new_job['fid'] = 0; // unknown farmer ID as yet
	$new_job['in'] = trim($paths['in']);
	$new_job['out'] = trim($paths['out']);
	$new_job['vw'] = (int) $transcode_options['vw'] * 1;
	$new_job['vh'] = (int) $transcode_options['vh'] * 1;
	$new_job['vb'] = (int) $transcode_options['vb'] * 1;
	$new_job['ab'] = (int) $transcode_options['ab'] * 1;
	$new_job['tsc'] = time();
	$new_job['tsu'] = time();
	
	// ok, add row
	try {
		$result = $farmdb->jobs->insert($new_job, array('safe' => true));
	} catch(MongoCursorException $e) {
		return false;
	}
	
	return true;
	
}

?>