<?php

include_once dirname(__FILE__) . '/requirement.php';
include_once dirname(__FILE__) . '/reqlist.php';


class DBReqConnect
{
	// Database settings
	protected $host = "localhost";
	protected $user = "wikiuser";
	protected $pass = "qwerty";
	protected $db   = "wikidb";
	protected $query = 'SELECT name, test,title, version, body, upward, comment,status,priority FROM ';
	protected $table = 'breeze_Reqs';

	public function readByName($name)
	{
		$cond = "name='".mysql_escape_string($name)."'";
		return $this->readOneReq($cond);
	}

	public function readOneReq($Condition)
	{
		$badres = new Requirement();
		$link = mysql_connect($this->host, $this->user, $this->pass);
		if (!$link)
		{
		    echo('Could not connect: ' . mysql_error());
			return $badres;
		}
		if( !mysql_set_charset('utf8',$link) )
		{
		    echo 'Could not set charset';
			return $badres;
		}

		if (!mysql_select_db($this->db, $link))
		{
		    echo 'Could not select database';
			return $badres;
		}

		$query=$this->query . $this->table;
		if( !empty($Condition) )
			$query .= ' WHERE ' . $Condition;
//		echo $query;
		$result = mysql_query($query, $link);

		if (!$result)
		{
		    echo "DB Error, could not query the database\n";
		    echo 'MySQL Error: ' . mysql_error();
			return $badres;
		}

		if( $row = mysql_fetch_assoc($result) )
		{
			$res = Requirement::reqFromRow($row);
			return $res;
		}
		mysql_close($link);

		return $badres;
	}

	public function readAllReqs($Prefix, $Condition = "status!='Deleted' AND status!='Reserved'")
	{
		$res = new ReqList();
		if( empty($Prefix) )
		{
			echo "Empty req prefix\n";
			return $res;
		}

		$link = mysql_connect($this->host, $this->user, $this->pass);
		if (!$link)
		{
		    echo('Could not connect: ' . mysql_error());
			return $res;
		}
		if( !mysql_set_charset('utf8',$link) )
		{
		    echo 'Could not set charset';
			return $res;
		}

		if (!mysql_select_db($this->db, $link))
		{
		    echo 'Could not select database';
			return $res;
		}

		$query=$this->query . $this->table . ' WHERE name like \''. mysql_escape_string($Prefix).'%\'';
		if( !empty($Condition) )
			$query .= ' AND ' . $Condition;

//		echo $query;
		$result = mysql_query($query, $link);

		if (!$result)
		{
		    echo "DB Error, could not query the database\n";
		    echo 'MySQL Error: ' . mysql_error();
			return $res;
		}

		while( $row = mysql_fetch_assoc($result) )
		{
			$req = Requirement::reqFromRow($row);
			$res->addReq($req);
		}
		mysql_close($link);

		return $res;
	}


	public function saveReq($req)
	{
		$link = mysql_connect($this->host, $this->user, $this->pass);
		if (!$link)
		{
		    echo('Could not connect: ' . mysql_error());
			return false;
		}
		if( !mysql_set_charset('utf8',$link) )
		{
		    echo 'Could not set charset';
			return false;
		}

		if (!mysql_select_db($this->db, $link))
		{
		    echo 'Could not select database';
			return false;
		}

		$query='insert into '.$this->table.'(name, title, version, body, upward, comment, test, priority, status) VALUES(\''.
			mysql_escape_string($req->name).'\',\''.
			mysql_escape_string($req->title).'\','.
			'1,\''.	// Version for new REQ=1
			mysql_escape_string($req->body).'\',\''.
			mysql_escape_string($req->upward).'\',\''.
			mysql_escape_string($req->comment).'\',\''.
			mysql_escape_string($req->test).'\',\''.
			mysql_escape_string($req->priority).'\',\''.
			mysql_escape_string($req->status).
			'\') ON DUPLICATE KEY UPDATE name=VALUES(name), title=VALUES(title), version=version+1, body=VALUES(body), upward=VALUES(upward), test=VALUES(test), status=VALUES(status), comment=VALUES(comment), priority=VALUES(priority)';
		echo $query;
		$result = mysql_query($query, $link);

		if (!$result)
		{
		    echo "DB Error, could not query the database\n";
		    echo 'MySQL Error: ' . mysql_error();
			return false;
		}

		mysql_close($link);

		return true;
	}
};
?>