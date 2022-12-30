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
