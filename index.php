<?php
header("Content-Type: application/json");
if(!empty($_GET['ticket_id']) && !empty($_GET['register_id']) && !empty($_GET['op'])) {
	$r = new Redis();
	$r->connect("127.0.0.1", "6379");

	$ticket_id = "ticket:".$_GET['ticket_id'];

	$user = $_GET['register_id'];
	$op = $_GET['op'];
	$key = "lock:" . $ticket_id;

	if($op == 'acquire' || $op == 'renew') {
		$nx = $r->setnx($key, $user);
		if ($nx || ($op == 'renew' && $r->get($key) == $user)) {
			// Because system can crash, give him 5 min.
			$r->expire($key, 60 * 3);
			echo json_encode(array("status" => true, "message" => "Ticket lock acquired."));
		} else {
			echo json_encode(array("status" => false, "message" => "Ticket is currently open at Register: ".$r->get($key)));
		}
	} elseif($op == 'unlock') {
		if($r->get($key) == $user) {
			$r->del($key);
			echo json_encode(array("status" => true, "message" => "Successfully unlocked ticket."));
		} else {
			echo json_encode(array("status" => false, "message" => "Failed to unlock ticket."));
		}
	} else {
		echo json_encode(array("status" => false, "message" => "Missing or invalid argument 'op'."));
	}
} else {
	echo json_encode(array("status" => false, "message" => "Missing or invalid argument(s) 'op', 'ticket_id', or 'register_id'."));
}
