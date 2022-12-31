<?php require $_SERVER['DOCUMENT_ROOT']."/api/private/core.php";
header("content-type: text/plain");

$Ticket = $_COOKIE['ticket'] ?? "None";

db::run(
	"INSERT INTO machineconfiguration (MachineAddress, Report, Ticket, Timestamp) VALUES (:MachineAddress, :Report, :Ticket, UNIX_TIMESTAMP())",
	[":MachineAddress" => GetIPAddress(), ":Report" => file_get_contents("php://input"), ":Ticket" => $Ticket]
);

echo "OK";