<? $this->displayView('components/header.php', array('title' => 'Import')); ?>
			<h1>
				<? echo $this->localize('Import'); ?>

			</h1>
			<table class="content">
				<thead class="head">
					<tr>
						<th class="field">
							<? echo $this->localize('Output'); ?>

						</th>
					</tr>
				</thead>
				<tbody class="body">
					<tr class="odd">
						<td class="field">
							<? echo /*$output*/$this->localize('Import completed successfully!'); ?>

						</td>
					</tr>
				</tbody>
			</table>
<? $this->displayView('components/footer.php'); ?>