<? $this->displayView('components/header.php', array('title' => 'Import')); ?>
			<h1>
				<a href="index" title="<? echo $this->localize('Import'); ?>"><? echo $this->localize('Import'); ?></a>
			</h1>
			<div class="options">
				<a class="option" href="<? echo $this->getConfiguration('cheeseUrl'); ?>" title="<? echo $this->localize('Objects'); ?>"><? echo $this->localize('Objects'); ?></a>
				<? if ($this->getConfiguration('sourcePathModeling')): ?>
				<a class="option" href="<? echo $this->getConfiguration('baseUrl'); ?>update" title="<? echo $this->localize('Update'); ?>"><? echo $this->localize('Update'); ?></a>
				<? endif; ?>
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
<? $coachingTestUrl = $this->getConfiguration('coachingTestUrl'); ?>
<? foreach ($Coachings as $n => $Coaching): ?>
					<tr id="group<? echo $n + 1; ?>" class="<? echo $n % 2 ? 'even' : 'odd'; ?> divider row">
						<td class="field data" colspan="<? if ($coachingTestUrl): ?>4<? else: ?>5<? endif; ?>">
							<? echo $Coaching->getKey(); ?> <em>(<? echo $this->localize('%d ' . (($count = count($Coaching->getObjects())) == 1 ? $this->localize('object') : $this->localize('objects')), $count); ?>)</em>
						</td>
						<? if ($coachingTestUrl): ?>
						<td class="field data right">
							<a class="external" href="<? echo sprintf($coachingTestUrl, $Coaching->getKey()); ?>" title="<? echo $this->localize('Coaching Test'); ?>"><? echo $this->localize('Coaching Test'); ?></a>
						</td>
						<? endif; ?>
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