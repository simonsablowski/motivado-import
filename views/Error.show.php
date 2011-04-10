<? $this->displayView('components/header.php', array('title' => 'Error')); ?>
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
					<tr class="<? if ($n + 1 == count($fields)) echo 'last '; echo $n % 2 ? 'odd' : 'even'; ?> divider">
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
<? $this->displayView('components/footer.php'); ?>