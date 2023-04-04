<?php
	ini_set('display_errors', 1);
	ini_set('memory_limit', '1024M');
	ini_set('max_execution_time', '600');
	ini_set('user_agent','Mozilla/4.0 (compatible; MSIE 7.0b; Windows NT 6.0)');
	error_reporting(E_ERROR ^ E_WARNING);
	
	$hindiCharactersRegex = '~^[\x{0900}-\x{097F}]+$/u';
	$englishCharatersRegex = '/[^\W_ ] /';
	
	$result = array();
	
	if(isset($_REQUEST["links"])){
		include_once('simple_html_dom.php');
		$links = trim($_REQUEST["links"]);
		$linksArray = explode(PHP_EOL,$links);
		$status = true;
		$errorArray = array();
		foreach($linksArray as $link){
			$content = @file_get_contents($link);
			
			if (!strpos($http_response_header[0], "200")) {  
			   $status = false;
			   array_push($result, array($link, "Fail", "Wrong Page"));
			   continue;
			}
			
			if($status){
				$plainText = file_get_html($link)->plaintext;			
				if ($status && !preg_match($hindiCharactersRegex, $plainText)) {
					$status = false;
					array_push($result, array($link, "Fail", "Other than Hindi Language characters Detected on Main Page"));
					continue;
				}
				if($status && preg_match($englishCharatersRegex,$plainText)) {
					$status = false;
					array_push($result, array($link, "Fail", "English Language Detected on Main Page"));
					continue;
				}	
			}
			
			if($status){				
				$html = file_get_html($link);			
				// Find all links
				$linkParts = parse_url($link);
				$linkParts['path'] = str_replace(basename($linkParts['path']), "", $linkParts['path']);
				$i=1;
				//Checking internal links for language errors
				foreach($html->find('a') as $element){
					$internalLink = "";
					if(isset(parse_url($element->href)['host'])){
						$internalLink = $element->href;
					} else {
						$internalLink = $linkParts['scheme'] . '://' . $linkParts['host'] . $linkParts['path'] .$element->href; 
					}
					$content = @file_get_contents($internalLink);
					if (!strpos($http_response_header[0], "200")) {  
					   $status = false;
					   array_push($result, array($link, "Fail", "Wrong Internal Page"));
					   continue;
					}
					if($status && $internalLink != "" && $internalLink != $link){
						$internalPagePlainText = file_get_html($internalLink)->plaintext;
						if ($status && !preg_match($hindiCharactersRegex, $internalPagePlainText)) {
							$status = false;
							array_push($result, array($link, "Fail", "Other than Hindi Language characters Detected on Internal Page"));
							continue;
						}
						if($status && preg_match($englishCharatersRegex,$internalPagePlainText)) {
							$status = false;
							array_push($result, array($link, "Fail", "English Language Detected on Internal Page"));
							continue;
						}						
					}
					$i++;
					if($i == 2){
						break;
					}
				}
				$i=1;
				// Find all images
				foreach($html->find('img') as $element){					
					$linkWithoutBasename = str_replace(basename($link), "", $link);
					$imageUrl = $linkWithoutBasename . $element->src;
					$img = "image.png";
					file_put_contents($img, file_get_contents($imageUrl));
					list($w, $h) = getimagesize($img);
					$filesize = filesize($img);
					//Quality answer for your image
					$quality = (101-(($w*$h)*3)/$filesize);
					if($quality < 50){
						$status = false;
					    array_push($result, array($link, "Fail", "Images not high resolution;"));
					    continue;
					}
					$i++;
					if($i == 2){
						break;
					}
				}
				
			}
			if($status){
				array_push($result, array($link, "Pass", ""));
			}
		}
	}
?>
<html>
	<head>
		<title>Coding Allstars Trial Task</title>
		<script language="javascript">
			function setFocus(){
				document.getElementById("links").focus();
			}
		</script>
		<style>
			body{
				font-family:arial;
				font-weight:12px;
			}

			h1{
				color:blue;
			}

			h3{
				color:red;
			}

			.linksFont{
				font-family:courier new;
				border: 1px solid #000;
				width: 100%;
			}

			.blueFont{
				color:blue;
			}

			.greenFont{
				color:green;
			}
		</style>
	</head>
	<body onload="setFocus();">
		<table>
			<tr>
				<td>
					<h1>Coding Allstars Trial Task</h1>
				</td>
			</tr>
			<form name="linksCheckingForm" method="post" action="">
			<tr>
				<td>
					<textarea name="links" id="links" cols="30" rows="10"><?php echo isset($_REQUEST["links"]) ? $_REQUEST["links"] : ""; ?></textarea>
				</td>
			</tr>
			<tr>
				<td>
					<input type="submit" value="Check Links" />
				</td>
			</tr>
			</form>
			<?php if($result && sizeof($result) > 0){ ?>
			<tr>
				<td>
					<table cellspacing="5" cellpadding="5" border="0" width="100%">
						<tr>
							<td colspan="3">
								<h3>
									Links Report
								</h3>
							</td>
						</tr>
						<?php 
						foreach($result as $res){
						?>
						<tr>
							<td class="linksFont">
								<?php echo $res[0];?>
							</td>
							<td class="linksFont">
								<?php echo $res[1];?>
							</td>
							<td class="linksFont">
								<?php echo $res[2];?>
							</td>
						</tr>
						<?php						
						}
						?>
					</table>
				</td>
			</tr>
			<?php } ?>
		</table>
	</body>
</html>