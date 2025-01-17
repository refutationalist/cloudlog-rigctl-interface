<?php
/**
 * @brief        rigctld API
 * @date         2018-12-03
 * @author       Tobias Mädel <t.maedel@alfeld.de>
 * @copyright    MIT-licensed
 *
 */
class rigctldAPI
{
	private $host; 
	private $port;
	private $socket = false; 

	// all I have available are Yaesu radios and the both do power
	// the same way.  Will need help to support other methods
	private $w_mult = 100;

	function __construct($host = "127.0.0.1", $port = 4532)
	{
		$this->host = $host; 
		$this->port = $port;

		$this->connect();
	}
	function __destruct()
	{
		fclose($this->fp);
	}

	public function connect()
	{
		$this->fp = fsockopen($this->host, $this->port, $errno, $errstr, 5);
		if (!$this->fp) 
			return false; 

		return true;
	}
	private function runCommand($command, $returnSize = 1)
	{
		if ($this->fp === false)
			return false; 

		if (feof($this->fp))
		{
			$this->fp = false;
			return false; 
		}
		
		stream_set_timeout($this->fp, 2);

		fwrite($this->fp, $command . "\n");
		$result = "";
		for ($i=0; $i < $returnSize; $i++)
		{ 
			$result .= trim(fgets($this->fp)) . "\n";
		}
		
		return trim($result);
	}

	public function getFrequencyAndMode()
	{
		$data = $this->runCommand("fm", 3); 
		if ($data === false)
			return false; 

		$data = explode("\n", $data); 

		return [
			"frequency" => $data[0],
			"mode" => $data[1],
			"passband" => $data[2]
		];
	}

	public function getAll()
	{
		$data = $this->runCommand("fm\get_level RFPOWER", 4); 
		if ($data === false)
			return false; 

		$data = explode("\n", $data); 

		return [
			"frequency" => $data[0],
			"mode" => $data[1],
			"passband" => $data[2],
			"power" => $data[3] * $this->w_mult
		];
	}
	

	public function getPower()
	{
		$thing = trim($this->runCommand("\get_level RFPOWER"));
		return $thing * $this->w_mult;

	}


	public function getFrequency()
	{
		return $this->runCommand("f");
	}

	public function getMode()
	{
		$mode = $this->runCommand("m", 2); 
		if ($mode === false)
			return false; 

		$mode = explode("\n", $mode); 

		return [
			"mode" => $mode[0],
			"passband" => $mode[1]
		];
	}
}
