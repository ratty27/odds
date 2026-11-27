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
 *	Get popularity mark from favorite rank
 */
function get_favorite_mark(favorite)
{
	let rank = parseInt(favorite, 10);
	if( rank == 1 )
	{
		return '◎';
	}
	if( rank == 2 )
	{
		return '○';
	}
	if( rank == 3 )
	{
		return '▲';
	}
	if( rank == 4 )
	{
		return '△';
	}
	return '';
}

/**
 *	Pick readable text color for a frame background.
 */
function get_contrast_text_color(hex)
{
	if( !hex )
	{
		return '#111111';
	}
	let raw = ('' + hex).replace('#', '');
	if( raw.length != 6 )
	{
		return '#111111';
	}
	let r = parseInt(raw.substring(0, 2), 16);
	let g = parseInt(raw.substring(2, 4), 16);
	let b = parseInt(raw.substring(4, 6), 16);
	let yiq = ((r * 299) + (g * 587) + (b * 114)) / 1000;
	return (yiq >= 140) ? '#111111' : '#ffffff';
}

/**
 *	Inline style for framed number cells.
 */
function get_frame_number_style(color)
{
	if( !color )
	{
		return '';
	}
	return 'background-color:' + color + ';color:' + get_contrast_text_color(color) + ';';
}

/**
 *	Odds type caption
 */
function layout_odds_caption(elemId, name, desc)
{
	let layout = '';
	layout += '<div class="odds-game-caption">';
	layout += '<h4>' + name + '</h4>';
	layout += '<div class="odds-game-caption-desc">' + desc + '</div>';
	layout += '</div>';

	let elem = document.getElementById( elemId );
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
 *	Escape a value for an HTML attribute
 */
function escape_attr(value)
{
	return String(value)
		.replace(/&/g, '&amp;')
		.replace(/"/g, '&quot;')
		.replace(/</g, '&lt;')
		.replace(/>/g, '&gt;');
}

/**
 *	Make a quick-bet odds cell button
 */
function make_matrix_quick_bet_cell(oddsTag, type, id0, id1, order0, name0, order1, name1)
{
	let layout = "<td class='odds_value text-center odds-game-matrix-bet-cell'>";
	layout += "<button type='button' class='odds-game-matrix-bet'";
	layout += " data-bet-type='" + type + "'";
	layout += " data-candidate-id0='" + id0 + "'";
	layout += " data-candidate-id1='" + id1 + "'";
	layout += " data-candidate-order0='" + order0 + "'";
	layout += " data-candidate-name0='" + escape_attr(name0) + "'";
	layout += " data-candidate-order1='" + order1 + "'";
	layout += " data-candidate-name1='" + escape_attr(name1) + "'";
	layout += " data-points='0'>";
	layout += "<span class='odds-game-matrix-bet__odds' id='" + oddsTag + "'></span>";
	layout += "<span class='odds-game-matrix-bet__label'>" + TXT_BET_ACTION + "</span>";
	layout += "</button></td>";
	return layout;
}

/**
 *	Make layout for quinella
 */
function layout_quinella(elemId, candidates, forBet, forQuickBet)
{
	let cellw = forQuickBet ? 14 : 12;

	let layout = '';
	for( let i = 0; i < candidates.length - 1; ++i )
	{
		layout += "<div class='table-responsive odds-game-matrix-wrap'>";
		layout += "<table class='table-bordered odds-game-matrix' style='table-layout: fixed; width: " + (8 + ((candidates.length - i - 1) * cellw)) + "ch;'>";
		let rowFrame = get_frame_number_style(candidates[i].frame_color);
		layout += "<tr><th scope='row' class='odds_value odds_number text-center" + (rowFrame ? " odds-game-number--framed" : "") + "' rowspan='2' style='width: 8ch;" + rowFrame + "'>" + (candidates[i].disp_order+1) + "</th>";
		for( let j = i + 1; j< candidates.length; ++j )
		{
			let colFrame = get_frame_number_style(candidates[j].frame_color);
			layout += "<th scope='col' class='odds_value odds_number text-center" + (colFrame ? " odds-game-number--framed" : "") + "'" + (colFrame ? " style='" + colFrame + "'" : "") + ">" + (candidates[j].disp_order+1) + "</th>";
		}
		layout += "</tr><tr>";
		for( let j = i + 1; j< candidates.length; ++j )
		{
			let tag = make_quinella_tag("odds_quinella", candidates[i].id, candidates[j].id);
			if( forQuickBet )
			{
				let id0 = candidates[i].id;
				let id1 = candidates[j].id;
				let order0 = candidates[i].disp_order + 1;
				let order1 = candidates[j].disp_order + 1;
				let name0 = candidates[i].name;
				let name1 = candidates[j].name;
				if( id0 > id1 )
				{
					id0 = candidates[j].id;
					id1 = candidates[i].id;
					order0 = candidates[j].disp_order + 1;
					order1 = candidates[i].disp_order + 1;
					name0 = candidates[j].name;
					name1 = candidates[i].name;
				}
				layout += make_matrix_quick_bet_cell(tag, 1, id0, id1, order0, name0, order1, name1);
			}
			else
			{
				layout += "<td class='odds_value text-center' id='" + tag + "'></td>";
			}
		}
		layout += "</tr>";
		if( forBet )
		{
			layout += "<tr><td class='text-center odds-game-matrix-label'>" + TXT_POINTS + "</td>";
			for( let j = i + 1; j< candidates.length; ++j )
			{
				let tag = make_quinella_tag("bet_quinella", candidates[i].id, candidates[j].id);
				layout += "<td class='odds_bet text-center'>";
				layout += "<input id='" + tag + "' name='" + tag + "' type='text' class='form-control' oninput='onModifyBet()' value='0'>";
				layout += "</td>";
			}
			layout += "</tr>";
		}
		layout += "</table></div>";
	}
	let elem = document.getElementById( elemId );
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
function layout_exacta(elemId, candidates, forBet, forQuickBet)
{
	let cellw = forQuickBet ? 14 : 12;

	let layout = '';
	for( let i = 0; i < candidates.length; ++i )
	{
		layout += "<div class='table-responsive odds-game-matrix-wrap'>";
		layout += "<table class='table-bordered odds-game-matrix' style='table-layout: fixed; width: " + (8 + ((candidates.length - 1) * cellw)) + "ch;'>";
		let rowFrame = get_frame_number_style(candidates[i].frame_color);
		layout += "<tr><th scope='row' class='odds_value odds_number text-center" + (rowFrame ? " odds-game-number--framed" : "") + "' rowspan='2' style='width: 8ch;" + rowFrame + "'>" + (candidates[i].disp_order+1) + "</th>";
		for( let j = 0; j< candidates.length; ++j )
		{
			if( i == j ) continue;
			let colFrame = get_frame_number_style(candidates[j].frame_color);
			layout += "<th scope='col' class='odds_value odds_number text-center" + (colFrame ? " odds-game-number--framed" : "") + "'" + (colFrame ? " style='" + colFrame + "'" : "") + ">" + (candidates[j].disp_order+1) + "</th>";
		}
		layout += "</tr><tr>";
		for( let j = 0; j< candidates.length; ++j )
		{
			if( i == j ) continue;
			let tag = make_exacta_tag("odds_exacta", candidates[i].id, candidates[j].id);
			if( forQuickBet )
			{
				layout += make_matrix_quick_bet_cell(
					tag,
					2,
					candidates[i].id,
					candidates[j].id,
					candidates[i].disp_order + 1,
					candidates[i].name,
					candidates[j].disp_order + 1,
					candidates[j].name
				);
			}
			else
			{
				layout += "<td class='odds_value text-center' id='" + tag + "'></td>";
			}
		}
		layout += "</tr>";
		if( forBet )
		{
			layout += "<tr><td class='text-center odds-game-matrix-label'>" + TXT_POINTS +"</td>";
			for( let j = 0; j< candidates.length; ++j )
			{
				if( i == j ) continue;
				let tag = make_exacta_tag("bet_exacta", candidates[i].id, candidates[j].id);
				layout += "<td class='odds_bet text-center'>";
				layout += "<input id='" + tag + "' name='" + tag + "' type='text' class='form-control' oninput='onModifyBet()' value='0'>";
				layout += "</td>";
			}
			layout += "</tr>";
		}
		layout += "</table></div>";
	}
	let elem = document.getElementById( elemId );
	if( elem )
	{
		elem.innerHTML = layout;
	}
}

/**
 *	Show past race results for a candidate name in a modal.
 *
 *	@param	string	name			Candidate name
 *	@param	object	historyMap		{ name: [ {game_name, result_rank}, ... ], ... }
 *	@param	string	titlePrefix		Modal title prefix (e.g. "Past results")
 */
function showCandidateHistory(name, historyMap, titlePrefix)
{
	let modalElem = document.getElementById('CandidateHistory');
	if( !modalElem )
	{
		return;
	}

	let titleElem = document.getElementById('CandidateHistoryLabel');
	if( titleElem )
	{
		titleElem.textContent = titlePrefix + ' - ' + name;
	}

	let emptyElem = document.getElementById('candidate_history_empty');
	let wrapElem = document.getElementById('candidate_history_table_wrap');
	let bodyElem = document.getElementById('candidate_history_body');
	if( !emptyElem || !wrapElem || !bodyElem )
	{
		return;
	}

	let rows = (historyMap && historyMap[name]) ? historyMap[name] : [];
	bodyElem.innerHTML = '';
	if( rows.length == 0 )
	{
		emptyElem.classList.remove('d-none');
		wrapElem.classList.add('d-none');
	}
	else
	{
		emptyElem.classList.add('d-none');
		wrapElem.classList.remove('d-none');
		for( let i = 0; i < rows.length; ++i )
		{
			let tr = document.createElement('tr');
			let tdName = document.createElement('td');
			tdName.className = 'align-middle';
			tdName.textContent = rows[i].game_name;
			let tdRank = document.createElement('td');
			tdRank.className = 'text-center align-middle';
			tdRank.textContent = '' + rows[i].result_rank;
			tr.appendChild(tdName);
			tr.appendChild(tdRank);
			bodyElem.appendChild(tr);
		}
	}

	let modal = bootstrap.Modal.getOrCreateInstance(modalElem);
	modal.show();
}

/**
 *	Bind click handlers for candidate history links.
 *
 *	@param	object	historyMap		{ name: [ {game_name, result_rank}, ... ], ... }
 *	@param	string	titlePrefix		Modal title prefix
 */
function bindCandidateHistoryLinks(historyMap, titlePrefix)
{
	let links = document.querySelectorAll('.odds-candidate-history-link');
	for( let i = 0; i < links.length; ++i )
	{
		links[i].addEventListener('click', function(ev)
		{
			ev.preventDefault();
			let name = this.getAttribute('data-name');
			if( name )
			{
				showCandidateHistory(name, historyMap, titlePrefix);
			}
		});
	}
}
