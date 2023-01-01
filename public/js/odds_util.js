/**
 *	Get odds as string for displaying
 */
function get_disp_odds(val)
{
	let s = '' + val;
	if( s.indexOf('.') < 0 )
	{
		s += '.0';
	}
	return s;
}

/**
 *	Make tag for two candidates of quinella
 */
function make_quinella_tag(prefix, id0, id1)
{
	if( id0 < id1 )
	{
		return prefix + "_" + id0 + "_" + id1;
	}
	else
	{
		return prefix + "_" + id1 + "_" + id0;
	}
}

/**
 *	Make layout for quinella
 */
function layout_quinella(elemId, candidates, forBet)
{
	let	layout = '';
	for( let i = 0; i < candidates.length - 1; ++i )
	{
		layout += "<div class='table-responsive'>";
		layout += "<table class='table-bordered' style='table-layout: auto;'>";
		layout += "<tr><td class='odds_value odds_number' rowspan='2'>" + (candidates[i].disp_order+1) + "</td>";
		for( let j = i + 1; j< candidates.length; ++j )
		{
			layout += "<td class='odds_value odds_number'>" + (candidates[j].disp_order+1) + "</td>";
		}
		layout += "</tr><tr>";
		for( let j = i + 1; j< candidates.length; ++j )
		{
			let tag = make_quinella_tag("odds_quinella", candidates[i].id, candidates[j].id);
			layout += "<td class='odds_value text-center' id='" + tag + "'></td>";
		}
		layout += "</tr>";
		if( forBet )
		{
			layout += "<tr><td>" + TXT_POINTS + "</td>";
			for( let j = i + 1; j< candidates.length; ++j )
			{
				let tag = make_quinella_tag("bet_quinella", candidates[i].id, candidates[j].id);
				layout += "<td class='odds_value text-center'>";
				layout += "<input id='" + tag + "' name='" + tag + "' type='text' class='form-control' style='width: 90px;' oninput='onModifyBet()' value='0'>";
				layout += "</td>";
			}
			layout += "</tr>";
		}
		layout += "</table></div><br>";
	}
	let	elem = document.getElementById( elemId );
	if( elem )
	{
		elem.innerHTML = layout;
	}
}

/**
 *	Make tag for two candidates of exacta
 */
function make_exacta_tag(prefix, id0, id1)
{
	return prefix + "_" + id0 + "_" + id1;
}

/**
 *	Make layout for exacta
 */
function layout_exacta(elemId, candidates, forBet)
{
	let	layout = '';
	for( let i = 0; i < candidates.length; ++i )
	{
		layout += "<div class='table-responsive'>";
		layout += "<table class='table-bordered' style='table-layout: auto;'>";
		layout += "<tr><td class='odds_value odds_number' rowspan='2'>" + (candidates[i].disp_order+1) + "</td>";
		for( let j = 0; j< candidates.length; ++j )
		{
			if( i == j ) continue;
			layout += "<td class='odds_value odds_number'>" + (candidates[j].disp_order+1) + "</td>";
		}
		layout += "</tr><tr>";
		for( let j = 0; j< candidates.length; ++j )
		{
			if( i == j ) continue;
			let tag = make_exacta_tag("odds_exacta", candidates[i].id, candidates[j].id);
			layout += "<td class='odds_value text-center' id='" + tag + "'></td>";
		}
		layout += "</tr>";
		if( forBet )
		{
	        layout += "<tr><td>" + TXT_POINTS +"</td>";
			for( let j = 0; j< candidates.length; ++j )
			{
				if( i == j ) continue;
				let	tag = make_exacta_tag("bet_exacta", candidates[i].id, candidates[j].id);
				layout += "<td class='odds_value text-center'>";
				layout += "<input id='" + tag + "' name='" + tag + "' type='text' class='form-control' style='width: 90px;' oninput='onModifyBet()' value='0'>";
				layout += "</td>";
			}
			layout += "</tr>";
		}
		layout += "</table></div><br>";
	}
	let	elem = document.getElementById( elemId );
	if( elem )
	{
		elem.innerHTML = layout;
	}
}
