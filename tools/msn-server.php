<?php

define('MSNP_EOL', "\r\n");

ini_set('error_reporting', E_ALL ^ E_NOTICE);
ini_set('display_errors', 1);

function eat(&$lines, $i, $length)
{
	$eaten = '';

	if( isset($lines[($i + 1)]) )
	{
		for( $j = $i + 1, $eaten_length = 0; $j < count($lines) && $eaten_length < $length; $j ++ )
		{
			if( ($eaten_length + strlen($lines[$j]) + 2) > $length )
			{
				echo 'Eaten_1 line #'.$j.' ('.($length - $eaten_length).')'.PHP_EOL;

				$eaten .= substr($lines[$j]."\r\n", 0, $length - $eaten_length);
				$lines[$j] = preg_replace('%\r\n$%', '', substr($lines[$j]."\r\n", $length - $eaten_length));
				$eaten_length = $length;
			}
			else
			{
				echo 'Eaten_2 line #'.$j.' ('.(strlen($lines[$j]) + 2).')'.PHP_EOL;

				$eaten .= $lines[$j]."\r\n";
				$lines[$j] = '';
				$eaten_length += strlen($lines[$j]) + 2;
			}
		}
	}

	return( $eaten );
}

set_time_limit(0);

$address = '127.0.0.1';
$port = 1863;

ob_implicit_flush();

$sock = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

//socket_set_option($sock, SOL_SOCKET, SO_REUSEADDR, 1);

socket_bind($sock, $address, $port) or die('Could not bind to address');  //0 for localhost

socket_listen($sock);

socket_set_nonblock($sock);

$clients = [];

while( true )
{
	// Waiting for a client...
	//$client = socket_accept($sock);

	// Client connected.
    if ($newsock = socket_accept($sock)) {
		echo '- socket_accept'.PHP_EOL;
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

	$client_email = null;
	$client_version = null;

	//while( true )
	//{
	if (count($clients)) {
		foreach ($clients AS $k => $v) {
			// Listen to client command(s).
			$input = socket_read($v, 1024000, PHP_BINARY_READ);

			// Command(s) received.
			//echo '-- socket_read'.PHP_EOL;

			if( in_array($input, array('', false), true) )
			{
				break;
			}

			file_put_contents('debug.log', 'Received '.date('Y-m-d H:i:s').':'.PHP_EOL.PHP_EOL.$input.PHP_EOL.PHP_EOL, FILE_APPEND);

			$lines = explode("\r\n", $input);

			//var_dump($lines);

			echo PHP_EOL;

			for( $i = 0; $i < count($lines); $i ++ )
			{
				$line = $lines[$i];

				if( in_array($line, array('', false), true) )
				{
					continue;
				}

				echo 'Request: >>> '.$line.PHP_EOL;

				$first_line = explode(' ', $line);

				var_dump($first_line);

				$response = $first_line[0].(( !empty($first_line[1]) ) ? ' '.$first_line[1].' ' : '');

				switch( $first_line[0] )
				{
					case 'VER':
						$client_version = $first_line[2];

						$response .= $first_line[2].(( !in_array($client_version, array('MSNP4','MSNP5','MSNP6','MSNP7','MSNP8','MSNP18','MSNP21')) ) ? ' '.implode(' ', array_slice($first_line, 3)) : '').MSNP_EOL;
						break;
						
					case 'INF':
						$response .= 'MD5'.MSNP_EOL;
						break;

					case 'CVR':
						$response .= $first_line[7].' '.$first_line[7].' '.$first_line[7].' http://www.domain.com http://www.domain.com'.MSNP_EOL;
						break;

					case 'USR':
						switch( $first_line[2] )
						{
							case 'TWN':
								switch( $first_line[3] )
								{
									case 'I':
										$client_email = $first_line[4];

										if( $client_version === 'MSNP8' ) {
											$response .= $first_line[2].' S lc=1033,id=507,tw=40,fs=1,ru=http%3A%2F%2Fmessenger%2Emsn%2Ecom,ct=1062764229,kpp=1,kv=5,ver=2.1.0173.1,tpf=43f8a4c8ed940c04e3740be46c4d1619'.MSNP_EOL;
										} else {
											$response .= $first_line[2].' S ct='.time().',rver=5.5.4177.0,wp=FS_40SEC_0_COMPACT,lc=1033,id=507,ru=http:%2F%2Fmessenger.msn.com,tw=0,kpp=1,kv=4,ver=2.1.6000.1,rn=1lgjBfIL,tpf=b0735e3a873dfb5e75054465196398e0'.MSNP_EOL;
										}
										break(3);
									case 'S':
										$MSG = ''.MSNP_EOL;

										$response .= 'OK '.$client_email.' myPenis 1 0'.MSNP_EOL;
										//$response .= 'SBS 0 null'.MSNP_EOL;
										//$response .= 'MSG Hotmail Hotmail '.strlen($MSG).MSNP_EOL;
										$response .= $MSG.MSNP_EOL;
										break(3);
								}
								break(2);

							case 'SSO':
								switch( $first_line[3] )
								{
									case 'I':
										$client_email = $first_line[4];

										if( $client_version === 'MSNP21' )
										{
											$response .= $first_line[2].' S MBI_KEY I88U9d9v7FLJ4d1kLfz9nafVMY7k35YTOKt1bVNGPqXevLecDFH6q5W2zo+kVg6C'.MSNP_EOL;
										}
										else
										{
											$response .= $first_line[2].' S MBI_KEY_OLD 8CLhG/xfgYZ7TyRQ/jIAWyDmd/w4R4GF2yKLS6tYrnjzi4cFag/Nr+hxsfg5zlCf'.MSNP_EOL;
										}
										break(3);
									case 'S':
										$response .= 'OK '.$client_email.' 1 0'.MSNP_EOL;

										if( $client_version === 'MSNP21' )
										{
											$response .= 'CHL 0 1663122458434562624782678054'.MSNP_EOL;

											$MSG = 'MIME-Version: 1.0'.MSNP_EOL
													.'Content-Type: text/x-msmsgsprofile; charset=UTF-8'.MSNP_EOL
													.'EmailEnabled: 1'.MSNP_EOL
													.'MemberIdHigh: 131328'.MSNP_EOL
													.'MemberIdLow: 31241544'.MSNP_EOL
													.'lang_preference: 1033'.MSNP_EOL
													.'country: FR'.MSNP_EOL
													.'Kid: 0'.MSNP_EOL
													.'Flags: 1074791491'.MSNP_EOL
													.'sid: 72652'.MSNP_EOL
													.'ClientIP: 90.66.38.248'.MSNP_EOL
													.'RouteInfo: msnp://25.127.98.147/00000000'.MSNP_EOL
													.MSNP_EOL;
											$MSG = ''.MSNP_EOL;

											$response .= 'MSG Hotmail Hotmail '.strlen($MSG).MSNP_EOL;
											$response .= $MSG;

											$NFY = 'Routing: 1.0'.MSNP_EOL
													.'To: 1:'.$client_email.';epid={00000000-0000-0000-0000-000000000000}'.MSNP_EOL
													.'From: 1:'.$client_email.''.MSNP_EOL
													.MSNP_EOL
													.'Reliability: 1.0'.MSNP_EOL
													.MSNP_EOL
													.'Notification: 1.0'.MSNP_EOL
													.'NotifNum: 0'.MSNP_EOL
													.'Uri: /user'.MSNP_EOL
													.'NotifType: Partial'.MSNP_EOL
													.'Content-Type: application/user+xml'.MSNP_EOL
													.'Content-Length: 53'.MSNP_EOL
													.MSNP_EOL
													.'<user><s n="PF" ts="2016-04-28T02:41:50Z"></s></user>';

											$response .= 'NFY PUT '.strlen($NFY).MSNP_EOL;
											$response .= $NFY;
										}
										elseif( $client_version === 'MSNP15' )
										{
											$MSG = 'MIME-Version: 1.0'.MSNP_EOL
													.'Content-Type: text/x-msmsgsprofile; charset=UTF-8'.MSNP_EOL
													.'LoginTime: '.time().MSNP_EOL
													.'EmailEnabled: 1'.MSNP_EOL
													.'MemberIdHigh: 83936'.MSNP_EOL
													.'MemberIdLow: 1113138176'.MSNP_EOL
													.'lang_preference: 1036'.MSNP_EOL
													.'preferredEmail: '.MSNP_EOL
													.'country: CA'.MSNP_EOL
													.'PostalCode: '.MSNP_EOL
													.'Gender: '.MSNP_EOL
													.'Kid: 0'.MSNP_EOL
													.'Age: '.MSNP_EOL
													.'BDayPre: '.MSNP_EOL
													.'Birthday: '.MSNP_EOL
													.'Wallet: '.MSNP_EOL
													.'Flags: 69643'.MSNP_EOL
													.'sid: 507'.MSNP_EOL
													.'kv: 6'.MSNP_EOL
													.'MSPAuth: 6Z1iKIC0bBbNlgb87D2SA1w3PNeweF7DyrUCimEnMdj1hrPLLMlDq5Hm1z0y9Kst92*My3jsIxVZ4VDG8TgBtyfw$$'.MSNP_EOL
													.'ClientIP: 24.111.111.111'.MSNP_EOL
													.'ClientPort: 60712'.MSNP_EOL
													.'ABCHMigrated: 1'.MSNP_EOL
													.'BetaInvites: 30'.MSNP_EOL;
											$MSG = ''.MSNP_EOL;

											$response .= 'SBS 0 null'.MSNP_EOL;
											$response .= 'MSG Hotmail Hotmail '.strlen($MSG).MSNP_EOL;
											$response .= $MSG.MSNP_EOL;
										}
										elseif( $client_version === 'MSNP18' )
										{
											$MSG = 'MIME-Version: 1.0'.MSNP_EOL
													.'Content-Type: text/x-msmsgsprofile; charset=UTF-8'.MSNP_EOL
													.'LoginTime: '.time().MSNP_EOL
													.'EmailEnabled: 1'.MSNP_EOL
													.'MemberIdHigh: 83936'.MSNP_EOL
													.'MemberIdLow: 1113138176'.MSNP_EOL
													.'lang_preference: 1036'.MSNP_EOL
													.'preferredEmail: '.MSNP_EOL
													.'country: CA'.MSNP_EOL
													.'PostalCode: '.MSNP_EOL
													.'Gender: '.MSNP_EOL
													.'Kid: 0'.MSNP_EOL
													.'Age: '.MSNP_EOL
													.'BDayPre: '.MSNP_EOL
													.'Birthday: '.MSNP_EOL
													.'Wallet: '.MSNP_EOL
													.'Flags: 69643'.MSNP_EOL
													.'sid: 507'.MSNP_EOL
													.'kv: 6'.MSNP_EOL
													.'MSPAuth: 6Z1iKIC0bBbNlgb87D2SA1w3PNeweF7DyrUCimEnMdj1hrPLLMlDq5Hm1z0y9Kst92*My3jsIxVZ4VDG8TgBtyfw$$'.MSNP_EOL
													.'ClientIP: 24.111.111.111'.MSNP_EOL
													.'ClientPort: 60712'.MSNP_EOL
													.'ABCHMigrated: 1'.MSNP_EOL
													.'BetaInvites: 30'.MSNP_EOL;
											$MSG = ''.MSNP_EOL;

											$response .= 'SBS 0 null'.MSNP_EOL;
											$response .= 'MSG Hotmail Hotmail '.strlen($MSG).MSNP_EOL;
											$response .= $MSG.MSNP_EOL;
											$response .= 'UBX 1:'.$client_email.' 0'.MSNP_EOL;
										}
										break(3);
								}
								break(2);

							case 'SHA':
								switch( $first_line[3] )
								{
									case 'A':
										$response .= 'OK '.$client_email.' 0 0'.MSNP_EOL;
										break;
								}
								break;
							
							case 'MD5':
								switch( $first_line[3] )
								{
									case 'I':
										$response .= 'MD5 S 1013928519.693957190'.MSNP_EOL;
										break;
									case 'S':
										$response .= 'OK gordon@freeman.com My%20Dick%20Long'.MSNP_EOL;
										break;
								}
								break(2);
						}
						break;

					case 'SYN':
						$response .= '27 5 4'.MSNP_EOL;
						$response .= 'GTC A'.MSNP_EOL;
						$response .= 'BLP AL'.MSNP_EOL;
						$response .= 'PRP PHH 01%20234'.MSNP_EOL;
						$response .= 'PRP PHM 56%20789'.MSNP_EOL;
						$response .= 'LSG 0 Other%20Contacts 0'.MSNP_EOL;
						$response .= 'LSG 1 Coworkers 0'.MSNP_EOL;
						$response .= 'LSG 2 Friends 0'.MSNP_EOL;
						$response .= 'LSG 3 Family 0'.MSNP_EOL;
						$response .= 'LST carol@passport.com Carol 3 0'.MSNP_EOL;
						$response .= 'BPR PHM 9876'.MSNP_EOL;
						$response .= '-54321\r\n'.MSNP_EOL;
						$response .= 'BPR PHW 45%206789'.MSNP_EOL;
						$response .= 'CHG 9 NLN 0'.MSNP_EOL;
						break;

					case 'OUT':
						break(3);

					case 'ADL': // - Add users to your contact lists
						// ADL 44 15/
						// <ml l="1"></ml>/
						eat($lines, $i, $first_line[2]);

						$response .= 'OK'.MSNP_EOL;
						break;

					case 'PRP': // - Initial settings download - Mobile settings and display name
						// PRP 45 MFN tristanleboss@msn.com/
						$response .= implode(' ', array_slice($first_line, 2)).MSNP_EOL;
						break;

					case 'CHG': // - Change client's online status
						// CHG 46 NLN 1347207228 /
						var_dump(urldecode($first_line[4]));
						$response .= implode(' ', array_slice($first_line, 2)).MSNP_EOL;
						break;

					case 'UUX':
						// UUX 47 80/
						// <Data><PSM></PSM><CurrentMedia></CurrentMedia><MachineGuid></MachineGuid></Data>/
						eat($lines, $i, $first_line[2]);

						$response .= '0'.MSNP_EOL;
						break;

					case 'BLP': // - Initial settings download
						// BLP 48 BL/

						$response .= implode(' ', array_slice($first_line, 2)).MSNP_EOL;
						break;

					case 'PNG':
						$response = 'QNG 60'.MSNP_EOL;
						break;

					case 'QRY':
						eat($lines, $i, $first_line[3]);

						$response = trim($response).MSNP_EOL;
						break;

					case 'DEL':
						eat($lines, $i, $first_line[2]);

						$response .= 'OK'.MSNP_EOL;
						break;

					case 'PUT':
						eat($lines, $i, $first_line[2]);

						$response .= 'OK 0'.MSNP_EOL;
						break;

					default:
						$response = 'OUT';
						break;
				}

				file_put_contents('debug.log', 'Sent '.date('Y-m-d H:i:s').':'.PHP_EOL.PHP_EOL.$response.PHP_EOL.PHP_EOL, FILE_APPEND);

				socket_write($v, $response);

				echo 'Response: <<< '.$response.PHP_EOL;

				if( $response === 'OUT' )
				{
					socket_close($clients[$k]);
					unset($clients[$k]);

					echo '- socket_close'.PHP_EOL;
				}
			}
		}
	}
}

socket_close($sock);
