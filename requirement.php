<?php
class Requirement
{
	public $name;
	public $title;
	public $body;
	public $upward;
	public $comment;
	public $status;
	public $version;
	public $test;

	public static function reqFromRow($row)
	{
//		echo "Create from Row '" . $row['name'] . "'\n";
		$res = new Requirement();

		$res->name = $row['name'];
		$res->title = $row['title'];
		$res->body = $row['body'];
		$res->upward = $row['upward'];
		$res->comment = $row['comment'];
		$res->status = $row['status'];
		$res->version = $row['version'];
		$res->test = $row['test'];

		if( empty($res->status) )
			$res->status = 'New';
		return $res;
	}

	public static function reqFromXML($name, $req)
	{
		$res = new Requirement();

		$res->name = $name;
		$res->title = $req->title;
		$res->body = $req->body;
		$res->upward = $req->upward;
		$res->comment = $req->comment;
		$res->status = $req->status;
		$res->version = $req->version;
		$res->test = $req->test;

		if( empty($res->status) )
			$res->status = 'New';

		return $res;
	}

	public function isValid()
	{
		if( empty($this->name) )
			return FALSE;

		return TRUE;
	}

	public function getPrefix()
	{
		$res = $this->name;
		$pos = strrpos($this->name, "-");
		if( $pos === false)
			return $res;
		$res = substr($this->name, 0, $pos);

		$pos = strrpos($res, "-");
		if( $pos === false)
			return $res;
		$res = substr($this->name, 0, $pos);

		return $res;
	}

	public function getUpwardReqs()
	{
		$res = array();
		$regex = '/([A-z0-9\-]+)\ *,*/s';
		if( preg_match_all( $regex, $this->upward, $matches, PREG_PATTERN_ORDER ) )
		{
			$res = '';
			$j=1;
			for($i=0;$i<sizeof($matches[$j]);++$i)
			{
				$res[ $matches[$j][$i] ] = true;
			}
			return $res;
		}

		return $res;
	}


	public function makeReqLinks()
	{
		if( empty($this->upward) )
			return "";

		$res = '';
		$upreqs = $this->getUpwardReqs();
		foreach( $upreqs as $key => $value )
		{
			$res = $res . '[['.$key.']] ';
		}

		return $res;
	}

	public function showReq( $showPrefix = false )
	{
		$text = "
		<table border=1 width=90%>
			<tr>";
		if( $showPrefix )
		{
				$text .= "<td rowspan=2> [[".$this->getPrefix()."]]:".$this->name."&nbsp;</td><td colspan=2>Title:".$this->title."</td>";
		}
		else
		{
				$text .= "<td rowspan=2> [[".$this->name."]]&nbsp;</td><td colspan=2>Title:".$this->title."</td>";
		}
		$text .= "</tr>
			<tr>
				<td>Status:".$this->status."</td><td>Version:".$this->version."</td>
			</tr>
			<tr>
				<td colspan=3>".$this->body."&nbsp;</td>
			</tr>
			<tr>
				<td colspan=3>Test:".$this->test."</td>
			</tr>
			<tr>
				<td colspan=3>Comment:".$this->comment."</td>
			</tr>
			<tr>
				<td colspan=3>Upward req:".$this->makeReqLinks()."</td>
			</tr>
		</table><br/>";

		return $text;
	}
};
?>