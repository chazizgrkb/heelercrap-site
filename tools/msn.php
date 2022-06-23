<?php
define('MSNP_EOL', "\r\n");

require ('lib/common.php');
use Codedungeon\PHPCliColors\Color;

ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

// Set time limit to indefinite execution
set_time_limit(0);

// Set the ip and port we will listen on
$address = '127.0.0.1';
$port = 1863;

ob_implicit_flush();

// Create a TCP Stream socket
$sock = socket_create(AF_INET, SOCK_STREAM, 0);

// Bind the socket to an address/port
socket_bind($sock, $address, $port) or die('Could not bind to address');

// Start listening for connections
socket_listen($sock);

// Non block socket type
socket_set_nonblock($sock);

// Clients
$clients = [];

echo Color::GREEN, 'Starting on ' . $address . ' (port ' . $port . ')', Color::RESET, PHP_EOL;

// Loop continuously
while (true)
{
	// Accept new connections
	if ($newsock = socket_accept($sock))
	{
		//if (is_resource($newsock)) {
		// Write something back to the user
		//socket_write($newsock, ">", 1).chr(0);
		// Non bloco for the new connection
		socket_set_nonblock($newsock);
		// Do something on the server side
		echo "New client connected\n";
		// Append the new connection to the clients array
		$clients[] = $newsock;
		//}
		
	}

	// Polling for new messages
	if (count($clients))
	{
		foreach ($clients AS $k => $v)
		{
			$goodbye = 0;
			// Check for new messages
			$string = '';
			if ($char = socket_read($v, 1024000))
			{
				$string = $char;
			}
			// New string for a connection
			if ($string)
			{
				echo "Client $k: >>> $string";
				$string = mb_substr($string,0,-2);
				$lines = explode("\r\n", $string);
				//var_dump($lines);
				for ($i = 0;$i < count($lines);$i++)
				{
					$line = $lines[$i];
					
					if( in_array($line, array('', false), true) )
					{
						break;
					}
					
					$first_line = explode(' ', $string);
					var_dump($first_line);
					$response = $first_line[0] . ((!empty($first_line[1])) ? ' ' . $first_line[1] . ' ' : '');
					switch ($first_line[0])
					{
						case 'VER':
							$client_version = $first_line[2];
							$response .= $first_line[2] . ((!in_array($client_version, array(
								'MSNP8',
								'MSNP18',
								'MSNP21'
							))) ? ' ' . implode(' ', array_slice($first_line, 3)) : '').MSNP_EOL;
							printNote('Client version is ' . $client_version);
						break;

						case 'CVR':
							$response .= "6.0.0602 6.0.0602 1.0.0000 http://download.microsoft.com/download/8/a/4/8a42bcae-f533-4468-b871-d2bc8dd32e9e/SETUP9x.EXE http://messenger.msn.com".MSNP_EOL;
						break;
						
						case 'INF':
							$response .= 'MD5'.MSNP_EOL;
							break;

						case 'CHG':
							$response .= implode(' ', array_slice($first_line, 2)).MSNP_EOL;
							break;
						
						case 'PNG':
							$response = "QNG";
							break;

						case 'USR':
							switch( $first_line[3] )
							{
								case 'I':
									$client_email = $first_line[4];

									$response .= $first_line[2].' S ct='.time().',rver=5.5.4177.0,wp=FS_40SEC_0_COMPACT,lc=1033,id=507,ru=http:%2F%2Fmessenger.msn.com,tw=0,kpp=1,kv=4,ver=2.1.6000.1,rn=1lgjBfIL,tpf=b0735e3a873dfb5e75054465196398e0'.MSNP_EOL;
									break;
								case 'S':
									$response .= 'OK '.$client_email.' example%20display%20name 1 0'.MSNP_EOL;
									break;
							}
						break;

					case 'SYN':
						$response .= '27 5 4'.MSNP_EOL;
						$response .= 'GTC A'.MSNP_EOL;
						$response .= 'BLP AL'.MSNP_EOL;
						$response .= 'LSG 0 Other%20Contacts 0'.MSNP_EOL;
						$response .= 'LSG 1 Coworkers 0'.MSNP_EOL;
						$response .= 'LSG 2 Friends 0'.MSNP_EOL;
						$response .= 'LSG 3 Family 0'.MSNP_EOL;
						$response .= 'LST carol@passport.com Carol 3 0'.MSNP_EOL;
						$response .= 'LST gordon@freeman.com GordonFreeman 3 0'.MSNP_EOL;
						$response .= 'BPR PHM 9876'.MSNP_EOL;
						$response .= '-54321\r\n'.MSNP_EOL;
						$response .= 'BPR PHW 45%206789'.MSNP_EOL;
						$response .= 'CHG 9 NLN 0'.MSNP_EOL;
						break;

						case 'OUT':
						$goodbye = 1;
							socket_close($clients[$k]);
							unset($clients[$k]);
							printNote('Goodbye!');
						break;

						default:
							printNote($first_line[0] . ' is not implemented!');
						break;
					}
					if ($goodbye != 1) {
					socket_write($clients[$k], $response);
					}
				}
				printNote("Client $k: <<< $response");
			}
			else
			{
			}
		}
	}
}

// Close the master sockets
socket_close($sock);