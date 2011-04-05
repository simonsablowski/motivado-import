<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en">
	<head>
		<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>
		<meta http-equiv="Content-Language" content="en"/>
		<title><? echo $this->localize('Error'); ?></title>
		<base href="<? echo $this->getApplication()->getConfiguration('basePath'); ?>"/>
		<link href="web/css/style.css" rel="stylesheet" title="Default" type="text/css" />
	</head>
	<body>
		<div id="document">
			<h1>
				<? echo $this->localize('Unfortunately,'); ?>

			</h1>
			<h2>
				<? echo $this->localize('we encountered an error:'); ?>

			</h2>
			<table class="content">
				<thead class="head">
					<tr>
						<th class="field">
							<? echo $this->localize('Field'); ?>

						</th>
						<th>
							<? echo $this->localize('Value'); ?>

						</th>
					</tr>
				</thead>
				<tbody class="body">
<? $fields = array('Type', 'Message'); if ($this->getApplication()->getConfiguration('debugMode')) $fields = array_merge($fields, array('Details', 'Trace')); ?>
<? foreach ($fields as $n => $field): ?>
					<tr class="<? if ($n + 1 == count($fields)) echo 'last '; echo $n % 2 ? 'odd' : 'even'; ?>">
						<td class="field">
							<? echo $this->localize($field); ?>

						</td>
						<td>
<? $getter = 'get' . $field; ?>
<? if ($field != 'Details' && $field != 'Trace'): ?>
							<? echo $this->localize($Error->$getter()); ?>
<? else: ?>
							<div class="highlight">
								<? var_dump($Error->$getter()); ?>
							</div>
<? endif; ?>

						</td>
					</tr>
<? endforeach; ?>
				</tbody>
			</table>
		</div>
	</body>
</html>