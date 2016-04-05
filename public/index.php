<?php
/**
 * @filesource   index.php
 * @created      02.04.2016
 * @author       Smiley <smiley@chillerlan.net>
 * @copyright    2016 Smiley
 * @license      MIT
 */



header('Content-type: text/html;charset=utf-8;');

?>
<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8"/>
	<meta name="viewport" content="width=device-width, initial-scale=1.0"/>
	<link rel="stylesheet" href="css/normalize.css"/>
	<link rel="stylesheet" href="css/style.css"/>
	<title>Threema Gateway</title>
</head>
<body>

	<form id="encrypt" action="#">
		<ul>
			<li>
				<label for="private">private key</label>
				<input id="private" name="private" type="text" />
			</li>
			<li>
				<label for="public">public key</label>
				<input id="public" name="public" type="text" />
			</li>
			<li>
				<label for="message">message</label>
				<textarea name="message" id="message" cols="30" rows="5"></textarea>
			</li>
		</ul>
		<input type="hidden" name="form" value="encrypt" />
		<button type="submit">start</button>
		<div id="encrypt-result" style="display: none"></div>
	</form>

<script src="https://cdnjs.cloudflare.com/ajax/libs/prototype/1.7.3/prototype.js"></script>
<script>
	(function(){

		$$('form').invoke('observe', 'submit', function(event){
			Event.stop(event);

			var params = event.target.serialize(true);
			new Ajax.Request('gateway.php', {
//				method: 'get',
				parameters: params,
				onSuccess: function(r){
					$(params.form + '-result').update(r.responseJSON.result).show();
					console.log(r.responseJSON);
				}
			});


		});

	})()
</script>
</body>
</html>
