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
				<tbody class="body">
<? foreach ($Coachings as $n => $Coaching): ?>
					<tr class="divider">
						<td class="field data" colspan="5">
							<? echo $Coaching->getKey(); ?> (<? echo $this->localize('%d objects', count($Coaching->getObjects())); ?>)

						</td>
					</tr>
<? foreach ($Coaching->getObjects() as $m => $Object): ?>
					<tr class="<? echo $m % 2 ? 'even' : 'odd'; ?>">
						<td class="<? if ($m + 1 == count($Coaching->getObjects())): ?>last <? endif; ?>number field">
							<? echo $m + 1; ?>

						</td>
						<td class="data field">
							<? echo $Object->getType(); ?>

						</td>
						<td class="data field">
							<? echo $Object->getKey(); ?>

						</td>
						<td class="field">
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