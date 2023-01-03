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
 *	Odds type caption
 */
function layout_odds_caption(elemId, name, desc)
{
    let layout = '';
    layout += '<table style="table-layout: fixed; width: 100%;">';
    layout += '<tr>';
    layout += '<td class="col-md-7"><h4>' + name + '</h4></td>';
    layout += '<td class="col-md-3 text-end">' + desc + '</td>';
    layout += '</tr>';
    layout += '</table>';

	let	elem = document.getElementById( elemId );
	if( elem )
	{
		elem.innerHTML = layout;
	}
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
	let cellw = 100 / candidates.length;
	let	layout = '';
	for( let i = 0; i < candidates.length - 1; ++i )
	{
		layout += "<div class='table-responsive'>";
		layout += "<table class='table-bordered' style='table-layout: fixed; width: " + ((candidates.length - i) * cellw) + "%;'>";
		layout += "<tr><th scope='row' class='odds_value odds_number text-center' rowspan='2'>" + (candidates[i].disp_order+1) + "</th>";
		for( let j = i + 1; j< candidates.length; ++j )
		{
			layout += "<th scope='col' class='odds_value odds_number text-center'>" + (candidates[j].disp_order+1) + "</th>";
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
			layout += "<tr><td class='text-center'>" + TXT_POINTS + "</td>";
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
		layout += "<table class='table-bordered' style='table-layout: fixed; width: 100%;'>";
		layout += "<tr><th scope='row' class='odds_value odds_number text-center' rowspan='2'>" + (candidates[i].disp_order+1) + "</th>";
		for( let j = 0; j< candidates.length; ++j )
		{
			if( i == j ) continue;
			layout += "<th scope='col' class='odds_value odds_number text-center'>" + (candidates[j].disp_order+1) + "</th>";
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
	        layout += "<tr><td class='text-center'>" + TXT_POINTS +"</td>";
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
