<?php

include_once dirname(__FILE__) . '/requirement.php';

class ReqList
{
	protected $reqs = array();
	protected $colors_priority = array("#a50026", "#d73027", "#f46d43", "#fdae61", "#fee08b", "#ffffbf",
					    "#d9ef8b", "#a6d96a", "#66bd63", "#1a9850", "#006837");

	protected $colors_status = array('Unknown'=>'white', 'Reserved'=>'#377eb8','New'=>'#fbb4ae',
					'Agreed'=>'#decbe4','Implemented'=>'#ffff33','Tested'=>'#ccebc5',
					'Deleted'=>'black');

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
			$idx_priority = (sizeof($this->colors_priority)-1) * (100 - $value->priority) / 100;
			
			$index = $this->getPrefixIndex($prefixes, $value->getPrefix());
			$res = $res.'"'.$value->name.'" [URL="'.$value->name
				.'",label=<<TABLE BORDER="0" CELLBORDER="1" CELLSPACING="0">
				<TR>
					<TD PORT="f0" BGCOLOR="'.$colors[$index].'" ROWSPAN="2">'.$value->title.'</TD>
					<td BGCOLOR="'.$this->colors_priority[$idx_priority].'"><font COLOR="WHITE">'.$value->priority.'</font></td>
				</TR>
				<TR>
					<td BGCOLOR="'.$this->colors_status[$value->status].'"><font COLOR="WHITE" FACE="Courier"> </font></td>
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