<?php

include_once dirname(__FILE__) . '/requirement.php';

class ReqList
{
	protected $reqs = array();

	public function addReq($req)
	{
		$this->reqs[$req->name] = $req;
	}

	public function reqList()
	{
		$text = "
			<table border=1 width=90%>
			<tr>
				<td><b>Name</b></td><td><b>Title</b></td><td><b>Upward</b></td><td><b>Status</b></td><td><b>Version</b></td>
			</tr>";

		foreach($this->reqs as $i => $value)
		{
			$text = $text."
			<tr>
				<td>[[".$i."]]</td><td>".$value->title."</td><td>".$value->makeReqLinks($value->upward)."</td><td>".$value->status."</td><td>".$value->version."</td>
			</tr>";
		}

		$text = $text . "</table>";

		return $text;
	}

	public function createGraph($layout, $width)
	{
		if( $layout=='' )
			$layout='fdp';

		$colors = array('white','lightblue','burlywood','salmon');
		$prefixes = array();

		$res = "<graphviz format='png' width=".$width.">\ndigraph{\nlayout=".$layout
			.";\nnode [shape=plaintext,fontname=undotum,terminus,georgia];\n";

		foreach($this->reqs as $i => $value)
		{
			$index = $this->getPrefixIndex($prefixes, $value->getPrefix());
			$res = $res.'"'.$value->name.'" [URL="'.$value->name
				.'",label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0">
				<TR>
					<TD PORT="f0" BGCOLOR="'.$colors[$index].'" ROWSPAN="2">'.$value->title.'</TD>
					<td BGCOLOR="red"><font COLOR="WHITE" FACE="Courier">High</font></td>
				</TR>
				<TR>
					<td BGCOLOR="blue"><font COLOR="WHITE">Tested</font></td>
				</TR>
				</TABLE>>'
				.'];'."\n";
		}

		$res = $res . "\n";

		foreach($this->reqs as $i => $value)
		{
			if( $value->upward=="" )
				continue;

			$upward=$value->getUpwardReqs();
			foreach($upward as $j => $reqvalue)
			{
				$res = $res . '"'.$value->name.'":f0->"'.$j.'":f0'."\n";
			}
		}

		$res = $res . "}\n</graphviz>";

		return $res;
	}

	protected function getPrefixIndex(&$prefixes, $pref)
	{
		$index = 0;
		foreach($prefixes as $i => $value)
		{
			if( $i==$pref )
				return $index;
			$index++;
		}

		$prefixes[$pref] = true;

		return count($prefixes)-1;
	}

};
?>