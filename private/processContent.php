<?php
//
// Description
// -----------
// This function will process raw text content into HTML.
//
// Arguments
// ---------
// ciniki:
// unprocessed_content:		The unprocessed text content that needs to be turned into html.
//
// Returns
// -------
//
function ciniki_web_processContent($ciniki, $unprocessed_content) {

	if( $unprocessed_content == '' ) { 
		return array('stat'=>'ok', 'content'=>'');
	}

	
	$processed_content = "<p class='intro'>" . preg_replace('/\n\s*\n/m', '</p><p>', $unprocessed_content) . '</p>';
//	$processed_content = preg_replace('/\r/m', '', $processed_content);
	$processed_content = preg_replace('/\n/m', '<br/>', $processed_content);
//	$processed_content = preg_replace('/h2><br\/>/m', 'h2>', $processed_content);


	// Create active links for urls specified with a href= infront
	$pattern = '#\b([^\"\'])((https?://?|www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
	$pattern = '#\b(((?<!href=\")https?://?|(?<!://)www[.])[^\s()<>]+(?:\([\w\d]+\)|([^[:punct:]\s]|/)))#';
	$callback = create_function('$matches', '
		error_log(serialize($matches));

		$prefix = $matches[1];
		$prefix = "";
		$url = $matches[1];
//		$url_parts = parse_url($url);
//		$text = parse_url($url, PHP_URL_HOST) . parse_url($url, PHP_URL_PATH);
//		$text = preg_replace("/^www./", "", $text);

//		$last = -(strlen(strrchr($text, "/"))) + 1;
//		if ($last < 0) {
//			$text = substr($text, 0, $last) . "&hellip;";
//		}
		return sprintf(\'%s<a href="%s">%s</a>\', $prefix, $url, $url);
	');
	$processed_content = preg_replace_callback($pattern, $callback, $processed_content);

	$processed_content = preg_replace('/((?<!mailto:|=|[a-zA-Z0-9._%+-])([a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,64})(?![a-zA-Z]|<\/[aA]>))/', '<a href="mailto:$1">$1</a>', $processed_content);

	//
	// Check for email addresses that should be linked
	//

	return array('stat'=>'ok', 'content'=>$processed_content);
}
?>
