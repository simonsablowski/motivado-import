<? $this->displayView('components/header.php', array('title' => 'Import')); ?>
			<h1>
				<a href="Importer/index" title="<? echo $this->localize('Import'); ?>"><? echo $this->localize('Import'); ?></a>
			</h1>
			<div class="options">
				<a class="option" href="<? echo $this->getConfiguration('cheeseUrl'); ?>" title="<? echo $this->localize('Objects'); ?>"><? echo $this->localize('Objects'); ?></a>
			</div>
			<table class="content">
				<thead class="head">
					<tr>
						<th class="field" colspan="5">
							<? echo $this->localize('Coachings'); ?>

						</th>
					</tr>
				</thead>
				<tbody class="body accordeon">
<? foreach ($Coachings as $n => $Coaching): ?>
					<tr id="group<? echo $n + 1; ?>" class="<? echo $n % 2 ? 'even' : 'odd'; ?> divider row">
						<td class="field data" colspan="5">
							<? echo $Coaching->getKey(); ?> <em>(<? echo $this->localize('%d objects', count($Coaching->getObjects())); ?>)</em>
						</td>
					</tr>
<? foreach ($Coaching->getObjects() as $m => $Object): ?>
					<tr class="group<? echo $n + 1; ?> row">
						<td class="<? if ($m + 1 == count($Coaching->getObjects())): ?>last <? endif; ?>number field">
							<? echo $m + 1; ?>

						</td>
						<td class="data field">
							<? echo $Object->getType(); ?>

						</td>
						<td class="data field">
							<? echo $Object->getKey(); ?>

						</td>
						<td class="main field">
							<? if ($title = $Object->getTitle()): ?><? echo $title; ?><? else: ?><? echo $Object->getDescription(); ?><? endif; ?>

						</td>
						<td class="main field">
<? $this->displayView('components/StdObject.php', array(
	'StdObject' => Json::decode($Object->getProperties()),
	'indent' => 7
)); ?>
						</td>
					</tr>
<? endforeach; ?>
					</tr>
<? endforeach; ?>
				</tbody>
			</table>
<? $this->displayView('components/footer.php'); ?>