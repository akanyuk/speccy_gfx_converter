<?php
require 'vendor/autoload.php';

require('src/ZXGFX.php');

$ZXGFX = new ZXGFX();

if (!empty($_FILES)) {
	// Установка опций. Корректность значений проверяется при установке.
	$ZXGFX->setOutputType($_POST['output_type']);
	$ZXGFX->setOutputScale($_POST['output_scale']);
	$ZXGFX->setPalette($_POST['palette']);
	$ZXGFX->setBorder($_POST['border']);
	$ZXGFX->setBorderColor($_POST['border_color']);

	if (isset($_POST['show_hidden_pixels'])) {
		$ZXGFX->setOption('showHiddenPixels', true);
		$ZXGFX->setHiddenColor($_POST['hidden_color']);
	}

	if ($_POST['filter_interleave'] != '-1') {
		$ZXGFX->setFilters('interleave', $_POST['filter_interleave']);
	}

	if ($_POST['filter_blur'] != '-1') {
		$ZXGFX->setFilters($_POST['filter_blur'], true);
	}
	
	if (isset($_POST['show']) || isset($_POST['download'])) {
		// Считывание загруженный файл.
		$tempFile = urldecode($_FILES['src_file']['tmp_name']);
		$handle = @fopen($tempFile, "r") or exit;
		$src_data = fread($handle, filesize($tempFile));
		fclose($handle);	

		// Загрузка в конвертер загруженного файла
		// Функция возвращает тип загруженного файла или false:
		// 	'screen', 'bw-screen', 'gigascreen', 'mgs', 'mg1' .. 'mg8'.
		if (!$type = $ZXGFX->loadData($src_data)) {
			exit('Incorrect uploaded file.');
		}
		
		if (isset($_POST['show'])) {
			// Вывод сконвертированного изображения на экран.
			header('Content-Disposition: filename="'.pathinfo($_FILES['src_file']['name'], PATHINFO_FILENAME).'.'.$ZXGFX->getOutputType().'"');
			$ZXGFX->show();
		} else if (isset($_POST['download'])) {
			// Скачивание сконвертированного файла
			// Параметром вызова функции можно указать имя скачиваемого файла без расширения
			$ZXGFX->download(pathinfo($_FILES['src_file']['name'], PATHINFO_FILENAME));
		}
/*
	Так же можно сохранить сконвертированный файл.
	$path - полный путь к сохраняемому файлу.
	$ZXGFX->save($path);
*/	
	} else if (isset($_POST['create-gif'])) {
		$ZXGFX->setOutputType('gif');
		$ZXGFX->setOption('forceOneFrame', true);

		$frame_delay = isset($_POST['frame_delay']) ? intval($_POST['frame_delay']) / 10 : 1;
		if ($frame_delay < 1) {
			$frame_delay = 1;
		} else if ($frame_delay > 9999) {
			$frame_delay = 9999;
		}
	
		$final_delay = isset($_POST['final_delay']) ? intval($_POST['final_delay']) / 10 : 1;
		if ($final_delay < 1) {
			$final_delay = 1;
		} else if ($final_delay > 9999) {
			$final_delay = 9999;
		}

		$tempFile = urldecode($_FILES['src_file']['tmp_name']);
		$zip = new ZipArchive;
		if (!$zip->open($tempFile)) {
			exit('Unable to open ZIP archive');
		}

		$frames = array();
		$delays = array();
		for ($i=0; $i < $zip->numFiles; $i++)	{
			$entry = $zip->getNameIndex($i);
			if (substr($entry, -1) == '/' ) {
				continue; // skip directories
			}

			$data = $zip->getFromIndex($i);
			if ($data === false) {
				exit('Unable to extract the file with index='.$i);
			}

			if (!$ZXGFX->loadData($data)) {
				continue;
			}
			$frame = $ZXGFX->generate();
			if ($frame !== false) {
				$frames[] = $frame;
				$delays[] = $frame_delay;
			}			
		}

		if (empty($frames)) {
			exit("No frames parsed");
		}

		$zip->close();		

		$delays[count($delays) - 1] = $final_delay;

        // GIFEncoder errors suppressing
		error_reporting(0);

        $gc = new GifCreator\GifCreator();
        try {
            $gc->create($frames, $delays);
        } catch (Exception $e) {
            exit($e->getMessage());
        }

        header('Content-type: image/gif');
		header('Content-Disposition: filename="'.pathinfo($_FILES['src_file']['name'], PATHINFO_FILENAME).'.gif"');
		echo  $gc->getGif();
	} else {
		exit("Bad request");
	}

	exit;
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="ru" lang="ru">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<meta name="language" content="ru" />
<title>GFX converter</title>
<style type="text/css">
	BODY { background: #ffffff; margin: 0; padding: 1em 2em; color: #222; }
	BODY, P { font: 15px Verdana, Geneva, sans-serif; }
	HR { margin: 5px 0 10px 0; padding: 0; border: none; border-bottom: 1px solid #bbbbbb;}
	H2 { font-weight: bold; font-size: 17px; color: #005dab; margin: 20px 0 10px 260px; }
	FORM { margin: 0; padding: 0; }
	FORM fieldset { border: 1px solid #005dab; padding: 10px; margin: 0; }
	FORM fieldset legend { font-weight: bold; color: #005dab; *margin-bottom: 10px; }
	FORM input { padding: 2px 5px 4px 5px; }
	FORM label { display: block; float: left; width: 250px; padding: 3px 5px 3px 0; margin: 0; text-align: right; }
    FORM label.inline { display: inline; float: initial; }
	FORM .hint { margin-left: 260px; margin-bottom: 10px; font-size: 13px; color: #444; }
	FORM .delimiter { clear: both; padding-bottom: 1em; }
</style>
</head><body>
	
<form method="POST" enctype="multipart/form-data" target="_blank" action="">
	<fieldset>
		<legend>Speccy GFX converter</legend>
		
		<label for="file">Select file</label>
		<input type="file" name="src_file" id="file"/>
		<div class="hint">Select ZX screen file or ZIP archive with multiple ZX files</div>
		<div class="delimiter"></div>
		
		<hr /> 

		<label for="output-scale-2">Output scale</label>
		<input type="radio" id="output-scale-1" name="output_scale" value="1"/>
        <label class="inline" for="output-scale-1">1x</label>

		<input type="radio" id="output-scale-2" checked name="output_scale" value="2"/>
        <label class="inline" for="output-scale-2">2x</label>

		<input type="radio" id="output-scale-3" name="output_scale" value="3"/>
        <label class="inline" for="output-scale-3">3x</label>

		<input type="radio" id="output-scale-05" name="output_scale" value="0.5"/>
        <label class="inline" for="output-scale-05">0.5x</label>
		<div class="delimiter"></div>
		
		<label for="palette">Palette</label>
		<select name="palette" id="palette">
<?php
	foreach ($ZXGFX->getPalettes() as $name=>$foo) {
		echo '<option value="'.$name.'">'.$name.'</option>';
	}
?>
		</select>
		<div class="delimiter"></div>
			
		<label for="border">Border</label>
		<select name="border" id="border">
<?php
	foreach ($ZXGFX->getBorders() as $name=>$foo) {
		$selected = ($name == 'small') ? ' SELECTED' : '';
		echo "<option value=\"$name\" $selected>$name</option>";
	}
?>
		</select>
		<select name="border_color" id="border_color">
			<option value="0" style="background-color: #000000;">black</option>
			<option value="1" style="background-color: #0000cc;">blue</option>
			<option value="2" style="background-color: #cc0000;">red</option>
			<option value="3" style="background-color: #cc00cc;">magenta</option>
			<option value="4" style="background-color: #00cc00;">green</option>
			<option value="5" style="background-color: #00cccc;">cyan</option>
			<option value="6" style="background-color: #cccc00;">yellow</option>
			<option value="7" style="background-color: #cccccc;">white</option>
		</select>
        <label for="border_color" style="display: none;"></label>
		<div class="delimiter"></div>

        <label>&nbsp;</label>
		<input type="checkbox" name="show_hidden_pixels" id="show_hidden_pixels"/>
        <label for="show_hidden_pixels" style="display: inline;float: none;">Show hidden pixels (INK=PAPER)</label>
		<select name="hidden_color" id="hidden_color">
			<option value="0">transparent</option>
<?php
		foreach ($ZXGFX->getHiddenColors() as $index=>$color) {
			$str_color = sprintf("%02s",dechex($color['R'])).sprintf("%02s",dechex($color['G'])).sprintf("%02s",dechex($color['B']));
			echo '<option value="'.$index.'" style="font-family: monospace; background-color: #'.$str_color.';">#'.$str_color.'</option>';
		}
?>
		</select>
        <label for="hidden_color" style="display: none;"></label>
		<div class="delimiter"></div>

		<label for="filter_interleave">Interleave</label>
		<select name="filter_interleave" id="filter_interleave">
			<option value="-1">disable</option>
			<option value="0">black</option>
			<option value="25">25% transparent</option>
			<option value="50">50% transparent</option>
			<option value="75">75% transparent</option>
		</select>
		<div class="delimiter"></div>
		
		<label for="filter_blur">Blur</label>
		<select name="filter_blur" id="filter_blur">
			<option value="-1">disable</option>
			<option value="light-blur">Light Blur</option>
			<option value="gaussian-blur">Gaussian Blur</option>
		</select>
		<div class="delimiter"></div>

		<h2>Conversion of one ZX Spectrum file</h2>
		
		<label for="output_type-png">Output type</label>
		<input type="radio" id="output_type-png" checked name="output_type" value="png"/>
        <label class="inline" for="output_type-png">PNG</label>

		<input type="radio" id="output_type-gif" name="output_type" value="gif"/>
        <label class="inline" for="output_type-gif">GIF</label>
		<div class="delimiter"></div>
		
		<label>&nbsp;</label>
		<input type="submit" name="show" value="Convert & open image" />
		<input type="submit" name="download" value="Convert & download image" />
		<div class="delimiter"></div>
		
		<h2>Make GIF animation of uploaded in ZIP archive files</h2>

		<label for="frame_delay">Frame delay, msec</label>
		<input type="text" id="frame_delay" name="frame_delay" value="2000" />
		<div class="delimiter"></div>
				
		<label for="final_delay">Final delay, msec</label>
		<input type="text" id="final_delay" name="final_delay" value="10000" />
		<div class="delimiter"></div>
			
		<label>&nbsp;</label>
		<input type="submit" name="create-gif" value="Convert & open GIF animation" />
	</fieldset>
</form>

<p>&copy; 2008-2022, Andrey <a href="http://nyuk.retropc.ru">nyuk</a> Marinov</p>

</body></html>