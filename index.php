<?php header('Content-Type: text/html; charset=utf-8'); function sanitize_title($title) { return str_replace(" ", "_", trim($title)); } function get_note_filename($title) { $sanitized_title = sanitize_title($title); return "forder/" . $sanitized_title . ".xml"; } function load_options_from_xml($xml_filename) { $options = []; if (file_exists($xml_filename)) { $xml = simplexml_load_file($xml_filename); foreach ($xml->children() as $option) { $value = (string) $option['value']; $text = (string) $option; $options[$value] = $text; } } return $options; } $xml_filename = 'Assets/options/downloadtext.xml'; $options = load_options_from_xml($xml_filename); function create_note($title, $content) { if (!$title || !$content) { echo "<script>window.location.href=window.location.href;</script>"; exit(); } $content = str_replace(PHP_EOL, '\n', $content); $filename = get_note_filename($title); $xml = new DOMDocument(); $xml->formatOutput = true; $note = $xml->appendChild($xml->createElement("note")); $note->appendChild($xml->createElement("title", $title)); $note->appendChild($xml->createElement("content", $content)); is_dir('forder') ?: mkdir('forder'); $xml->save($filename); echo "<script>window.location.href=window.location.href;</script>"; exit(); } function edit_note() { if ($_POST['title'] && $_POST['content']) { $title = $_POST['title']; $content = $_POST['content']; $new_title = $_POST['new_title'] ?? $title; $new_content = str_replace(PHP_EOL, '\n', $content); $old_filename = get_note_filename($title); if (!file_exists($old_filename)) return ""; $new_filename = get_note_filename($new_title); $xml = new DOMDocument(); $xml->formatOutput = true; $xml->load($old_filename); $note = $xml->getElementsByTagName("note")->item(0); $note->getElementsByTagName("title")->item(0)->nodeValue = $new_title; $note->getElementsByTagName("content")->item(0)->nodeValue = $new_content; $xml->save($new_filename); if ($old_filename !== $new_filename) unlink($old_filename); return ""; } } function delete_note($title) { if (!$title) { echo "<script>window.location.href=window.location.href;</script>"; exit(); } $filename = get_note_filename($title); if (!file_exists($filename)) { echo "<script>window.location.href=window.location.href;</script>"; exit(); } unlink($filename); echo "<script>window.location.href=window.location.href;</script>"; exit(); } function list_notes() { $files = is_dir('forder') ? scandir("forder") : []; $titles = []; foreach ($files as $file) { if (pathinfo($file, PATHINFO_EXTENSION) == "xml") { $xml = new DOMDocument(); try { $xml->load("forder/" . $file); if ($xml->getElementsByTagName("note")->length > 0) { $note = $xml->documentElement; $titles[] = $note->getElementsByTagName("title")->item(0)->nodeValue; } } catch (Exception $e) { echo ''; } } } sort($titles); return $titles; } function show_note_content($title) { if ($title) { $filename = get_note_filename($title); if (file_exists($filename)) { $xml = new DOMDocument(); $xml->load($filename); $note = $xml->documentElement; $content_elem = $note->getElementsByTagName("content")->item(0); return $content_elem->nodeValue; } else { return ""; } } else { return ""; } } if ($_SERVER['REQUEST_METHOD'] == "GET" && isset($_GET['action']) && $_GET['action'] == 'load') { $title = $_GET['title'] ?? ''; if ($title) { $filename = get_note_filename($title); if (file_exists($filename)) { $xml = new DOMDocument(); $xml->load($filename); $note = $xml->documentElement; $content_elem = $note->getElementsByTagName("content")->item(0); $content = $content_elem->nodeValue; echo json_encode(array('success' => true, 'content' => $content)); exit(); } else { echo json_encode(array('success' => false, 'message' => "")); exit(); } } else { echo json_encode(array('success' => false, 'message' => "")); exit(); } } if ($_SERVER['REQUEST_METHOD'] == "POST" && isset($_POST['action']) && $_POST['action'] == 'edit') { $title = $_POST['title'] ?? ''; $content = $_POST['content'] ?? ''; if ($title && $content) { $result = edit_note($title, $title, $content); echo json_encode(array('success' => true, 'message' => $result)); exit(); } } ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Заметки програмиста</title>
<style><!--
  @import url('Assets/css/base.css');@import url('Assets/css/layout.css');
  @import url('Assets/css/form.css');@import url('Assets/css/notes.css');
  @import url('Assets/css/modal.css');@import url('Assets/css/buttons.css');-->
  
#action-feedback {
    font-style: italic;
    color: green;
    background-color: #181818;
}

#textarea-container {
    position: relative;
    width: 99%;
    height: 500px;
    overflow: hidden;
	
}

#line-numbers {
    position: absolute;
    left: 0;
    top: 0;
    width: 30px;
    border-right: 1px solid #ccc;
    padding: 5px 0;
    box-sizing: border-box;
    overflow-y: auto;
    font-family: Consolas;
    font-size: 14px;
    line-height: 1.5;
    height: auto;
}

#line-numbers span {
    display: block;
    text-align: center;
    color: #fff;
}

#content {
    width: calc(100% - 30px);
    height: 100%;
    border: none;
    padding: 5px;
    box-sizing: border-box;
    margin-left: 30px;
    font-family: Arial, sans-serif;
    font-size: 14px;
    line-height: 1.5;
}

.highlight {
    font-family: Arial, sans-serif;
    line-height: 1.5;
    color: blue;
}

.ui-autocomplete {
    max-height: 100px;
    overflow-y: auto;
}

#newNoteButton {
    position: absolute;
    top: 5%;
    left: 10px;
    width: 16.3%;
    height: 5.5%;
    color: #9c9c9c;
    font-size: 16px;
    background-color: #181818;
    border-radius: 5px;
    padding: 5px 10px;
    cursor: pointer;
}

#newNoteButton:hover {
    margin-bottom: 10px;
    border: 1px solid #fff;
    border-radius: 5px;
    resize: vertical;
    background-color: #181818;
    color: #fff;
}

body {
    background-color: #302d2d;
    font-family: Arial, sans-serif;
    margin: 0;
    scrollbar-width: none;
    -ms-overflow-style: none;
    font-family: Consolas;
}

::-webkit-scrollbar {
    display: none;
}

input[type='text'],
textarea,
select,
input[type='submit'],
button {
    width: calc(100% - 15px);
    padding: 10px;
    margin-bottom: 10px;
    border: 1px solid #ccc;
    border-radius: 5px;
    resize: vertical;
    background-color: #181818;
    color: #fff;
	
}



button,
input[type="submit"] {
    padding: 10px;
    background-color: #181818;
    color: #fff;
    border: none;
    border-radius: none;
    cursor: pointer;
    width: 100%;
    transition: background-color 0.3s ease;
    font-family: Consolas;
}

button:hover,
input[type="submit"]:hover {
    background-color: #181818;
    border: 1px solid #fff;
    border-radius: 5px;
}

input {
    font-family: Consolas;
}

#notes-container {
    max-height: 660px;
    overflow-y: auto;
}

#noteList {
    list-style-type: none;
    padding: 10px;
    margin: 0;
    padding-bottom: 5px;
}

.note {
    width: 90%;
    height: 15px;
    background-color: #0d0d0d;
    color: #fff;
    padding: 10px;
    margin-bottom: 10px;
    overflow: hidden;
    cursor: pointer;
    border: none;
    border-radius: 5px;
    transition: background-color 0.3s ease;
}

.note:hover {
    background-color: #181818;
}

.dialog {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.7);
}

.dialog-content {
    background-color: #0d0d0d;
    margin: 15% auto;
    padding: 20px;
    border: 1px solid #fff;
    width: 40%;
    border-radius: 5px;
}

.close {
    color: #aaaa;
    float: right;
    font-size: 28px;
    font-weight: bold;
}

.close:hover,
.close:focus {
    color: #fff;
    text-decoration: none;
    cursor: pointer;
}

#sidebar {
    float: left;
    top: 70px;
    width: 18%;
    height: auto;
    position: relative;
    background-color: #2f2d2d;
}

#main-content {
    margin-left: auto;
    overflow-y: auto;
}

#action-buttons {
    margin-top: 10px;
    display: flex;
    flex-direction: row;
    justify-content: space-between;
    align-items: center;
    width: 100%;
}

#action-buttons button {
    flex: 1;
    width: auto;
    margin-right: 5px;
    font-size: 14px;
}

#glav-buttons {
    display: flex;
    flex-direction: row;
}

pre {
    background-color: #f4f4f4;
    padding: 10px;
    border: 1px solid #f4f4f4;
    border-radius: 5px;
    overflow-x: auto;
}



</style>
</head>
<body>
<button onclick="createNewNote()" id="newNoteButton">Новая заметка ✎</button>
<div id="sidebar">
<div id="notes-container">
<ul id="noteList">
<?php
$all_notes = list_notes();
foreach ($all_notes as $note) {
    echo "<div class=\"note\" data-title=\"" . htmlspecialchars($note) . "\">" . htmlspecialchars($note) . "</div>";
}
?>
</ul>
</div>
</div>
<div id="main-content">
<form method="post">
<input type="text" name="title" id="title" placeholder="Введите название..." value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">


<div id="action-buttons">
<button type="submit" name="action" value="create">Сохранить изменение</button>
<button id="deleteButton" type="button" name="action" value="delete" onclick="confirmDelete()">Удалить заметку</button>
<input type="text" name="search" id="search" placeholder="Найти🔍">
<button id="find-button" alt="Найти" onclick="searchAndScroll(); return false;">Найти</button>
<input type="text" name="replace" id="replace" placeholder="Заменить✎">
<button id="replace-button" onclick="replaceAll()">Заменить</button>
<div id="glav-buttons">
<button type="button" onclick="showSaveDialog()">Сохранить как</button>
<button id="copy-button" title="Скопировать текст" onclick="copyText()">Копировать текст</button>
</div>
</div>
<div id="textarea-container">
<div id="line-numbers"></div>
<textarea name="content" id="content" placeholder="Введите заметку..."><?= isset($_POST['content']) ? htmlspecialchars($_POST['content']) : '' ?></textarea>
<link rel="stylesheet" href="https://code.jquery.com/ui/1.13.1/themes/base/jquery-ui.css">
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://code.jquery.com/ui/1.13.1/jquery-ui.min.js"></script>
<script>
$(function(){$.ajax({url:'suggestions.json',dataType:'json',success:function(data){$("#content").autocomplete({minLength:1,source:function(request,response){var term=extractLast(request.term);var weightedSuggestions=[];$.each(data,function(index,item){var weight=calculateWeight(item,term);weightedSuggestions.push({label:item,weight:weight})});weightedSuggestions.sort(function(a,b){return b.weight-a.weight});var sortedSuggestions=[];$.each(weightedSuggestions,function(index,item){sortedSuggestions.push(item.label)});response(sortedSuggestions)},focus:function(event,ui){event.preventDefault()},select:function(event,ui){var terms=split(this.value);terms.pop();terms.push(ui.item.value);terms.push("");this.value=terms.join(" ");return!1}}).data("ui-autocomplete")._renderMenu=function(ul,items){var that=this;$.each(items,function(index,item){that._renderItemData(ul,item)});ul.addClass('scroll')};},error:function(xhr,status,error){console.error('Error:',status,error)}});function split(val){return val.split(/ \s*/)}function extractLast(term){return split(term).pop()}function calculateWeight(word,term){var firstLetterWeight=0;var secondLetterWeight=0;var thirdLetterWeight=0;if(word.charAt(0).toLowerCase()===term.charAt(0).toLowerCase()){firstLetterWeight=3}else if(word.charAt(1).toLowerCase()===term.charAt(0).toLowerCase()){firstLetterWeight=2}else if(word.charAt(2).toLowerCase()===term.charAt(0).toLowerCase()){firstLetterWeight=1}if(word.length>1){if(word.charAt(1).toLowerCase()===term.charAt(1).toLowerCase()){secondLetterWeight=3}else if(word.charAt(2).toLowerCase()===term.charAt(1).toLowerCase()){secondLetterWeight=2}else if(word.charAt(0).toLowerCase()===term.charAt(1).toLowerCase()){secondLetterWeight=1}}if(word.length>2){if(word.charAt(2).toLowerCase()===term.charAt(2).toLowerCase()){thirdLetterWeight=3}else if(word.charAt(0).toLowerCase()===term.charAt(2).toLowerCase()){thirdLetterWeight=2}else if(word.charAt(1).toLowerCase()===term.charAt(2).toLowerCase()){thirdLetterWeight=1}}return firstLetterWeight+secondLetterWeight+thirdLetterWeight}});
</script>
</div>
</div>
<div id="saveDialog" class="dialog">
<div class="dialog-content">
<span class="close" onclick="hideSaveDialog()">&times;</span>
<label for="fileName">Имя файла:</label>
<input type="text" id="fileName" value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
<label for="fileFormat">Формат файла:</label>
<select style="font-family: Consolas; margin-top: 10px;" id="fileFormat" onclick="showSearchField()">
<?php foreach ($options as $value => $text): ?>
<option value="<?= $value ?>"><?= $text ?></option>
<?php endforeach; ?>
</select>
<input type="text" id="fileFormatSearch" placeholder="Поиск по формату...">
<button onclick="saveNote()">Сохранить</button>
</div>
</div>
<div id="result" style="display: none;"><?php if ($_SERVER['REQUEST_METHOD'] == "POST") { $title = isset($_POST['title']) ? $_POST['title'] : ''; $content = isset($_POST['content']) ? $_POST['content'] : ''; $action = isset($_POST['action']) ? $_POST['action'] : ''; switch ($action) { case "create": create_note($title, $content); break; case "delete": delete_note($title); break; case "edit": edit_note($title, $title, $content); break; } } else { } ?></div>
</body>
</html>
<script>var select = document.getElementById('fileFormat'); var searchInput = document.getElementById('fileFormatSearch'); searchInput.addEventListener('input', function() { var searchValue = this.value.toLowerCase(); for (var i = 0; i < select.options.length; i++) { var option = select.options[i]; var optionText = option.textContent.toLowerCase(); if (optionText.indexOf(searchValue) !== -1) { option.style.display = ''; } else { option.style.display = 'none'; } } }); function showSaveDialog() { var titleValue = document.getElementById('title').value; var contentValue = document.getElementById('content').value; if (titleValue.trim() === '' || contentValue.trim() === '') { alert(''); return; } var defaultFileName = document.getElementById('title').value; document.getElementById('fileName').value = defaultFileName; document.getElementById('saveDialog').style.display = 'block'; } function hideSaveDialog() { document.getElementById('saveDialog').style.display = 'none'; } function saveNote() { var fileName = document.getElementById('fileName').value; var fileFormat = document.getElementById('fileFormat').value; var content = document.getElementById('content').value; var data = new Blob([content], { type: 'text/plain' }); var a = document.createElement('a'); a.href = window.URL.createObjectURL(data); a.download = fileName + '.' + fileFormat; document.body.appendChild(a); a.click(); } function confirmDelete() { if (confirm('')) { var form = document.createElement('form'); form.method = 'post'; form.action = window.location.href; var titleInput = document.createElement('input'); titleInput.type = 'hidden'; titleInput.name = 'title'; titleInput.value = document.getElementById('title').value; form.appendChild(titleInput); var actionInput = document.createElement('input'); actionInput.type = 'hidden'; actionInput.name = 'action'; actionInput.value = 'delete'; form.appendChild(actionInput); document.body.appendChild(form); form.submit(); } } function deleteNote() { location.reload(); } function copyContent() { var content = document.getElementById('content').value.replace(/\n/g, '\\n'); var tempTextArea = document.createElement('textarea'); tempTextArea.value = content; document.body.appendChild(tempTextArea); tempTextArea.select(); document.execCommand('copy'); document.body.removeChild(tempTextArea); } function loadNoteContent(title) { var xhr = new XMLHttpRequest(); xhr.onreadystatechange = function() { if (xhr.readyState === 4 && xhr.status === 200) { var response = JSON.parse(this.responseText); if (response.success) { document.getElementById('title').value = title; document.getElementById('content').value = response.content.replace(/\\n/g, '\n'); } else { alert(response.message); } } }; xhr.open('GET', '<?php echo $_SERVER['PHP_SELF']; ?>?action=load&title=' + encodeURIComponent(title), true); xhr.send(); } Array.from(document.getElementsByClassName('note')).forEach(function(element) { element.addEventListener('click', function() { loadNoteContent(this.getAttribute('data-title')); }); }); document.getElementById('content').addEventListener('input', function() { var title = document.getElementById('title').value; var content = this.value; var xhr = new XMLHttpRequest(); xhr.onreadystatechange = function() { if (xhr.readyState === 4) { if (xhr.status === 200) { console.log(''); } else { console.error('', xhr.responseText); } } }; xhr.open('POST', '<?php echo $_SERVER['PHP_SELF']; ?>', true); xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded'); xhr.send('title=' + encodeURIComponent(title) + '&content=' + encodeURIComponent(content) + '&action=edit'); }); function searchAndScroll() { var searchQuery = document.getElementById('search').value; var content = document.getElementById('content'); var text = content.value; var position = text.indexOf(searchQuery, currentPosition); if (position === -1) { position = text.indexOf(searchQuery); } if (position !== -1) { content.focus(); content.setSelectionRange(position, position + searchQuery.length); currentPosition = position + searchQuery.length; } } function replaceAll() { var searchQuery = document.getElementById('search').value; var replaceQuery = document.getElementById('replace').value; var content = document.getElementById('content'); content.value = content.value.replace(new RegExp(searchQuery, 'g'), replaceQuery); } function copyText() { document.getElementById('content').select(); document.execCommand('copy'); } function createNewNote() { document.getElementById('title').value = ''; document.getElementById('content').value = ''; } function updateLineNumbers() { var textarea = document.getElementById('content'); var lineNumbers = document.getElementById('line-numbers'); var lines = textarea.value.split('\n'); var lineNumbersHTML = ''; for (var i = 1; i <= lines.length; i++) { lineNumbersHTML += '<span>' + i + '</span>'; } lineNumbers.innerHTML = lineNumbersHTML; textarea.style.paddingLeft = lineNumbers.offsetWidth + 'px'; lineNumbers.style.top = -textarea.scrollTop + 'px'; } updateLineNumbers(); document.getElementById('content').addEventListener('input', updateLineNumbers); document.getElementById('content').addEventListener('scroll', updateLineNumbers);</script>
</body>
</html>