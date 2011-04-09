<? $this->displayView('components/header.php', array('title' => 'Import')); ?>
			<h1>
				<? echo $this->localize('Import'); ?>

			</h1>
			<table class="content">
				<thead class="head">
					<tr>
						<th class="field" colspan="5">
							<? echo $this->localize('Successfully imported:'); ?>

						</th>
					</tr>
				</thead>
				<tbody class="body">
<? foreach ($Coachings as $n => $Coaching): ?>
					<tr>
						<td class="field data" colspan="5">
							<? echo $Coaching->getKey(); ?> (<? echo $this->localize('%d objects', count($Coaching->getObjects())); ?>)

						</td>
					</tr>
<? foreach ($Coaching->getObjects() as $m => $Object): ?>
					<tr class="<? echo $m % 2 ? 'even' : 'odd'; ?>">
						<td class="<? if ($m + 1 == count($Coaching->getObjects())): ?>last <? endif; ?>number">
							<? echo $m + 1; ?>

						</td>
						<td class="field data">
							<? echo $Object->getType(); ?>

						</td>
						<td class="field data">
							<? echo $Object->getKey(); ?>

						</td>
						<td class="field">
							<? if ($title = $Object->getTitle()): ?><? echo $title; ?><? else: ?><? echo $Object->getDescription(); ?><? endif; ?>

						</td>
						<td class="field">
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