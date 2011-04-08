<? $this->displayView('components/header.php', array('title' => 'Import')); ?>
			<h1>
				<? echo $this->localize('Import'); ?>

			</h1>
			<table class="content">
				<thead class="head">
					<tr>
						<th class="field">
							<? echo $this->localize('Successfully imported:'); ?>

						</th>
					</tr>
				</thead>
				<tbody class="body">
<? foreach ($Coachings as $n => $Coaching): ?>
					<tr class="<? echo $n % 2 ? 'odd' : 'even'; ?>">
						<td class="field">
							<ol>
								<li>
									<p>
										<? echo $Coaching->getKey(); ?>

									</p>
									<p>
										<? echo $this->localize('%d objects:', count($Coaching->getObjects())); ?>

									</p>
									<ol>
<? foreach ($Coaching->getObjects() as $Object): ?>
										<li>
											<? echo $Object->getType(); ?><? if ($title = $Object->getTitle()): ?>: <? echo $title; ?><? endif; ?>

										</li>
<? endforeach; ?>
									</ol>
								</li>
							</ol>
						</td>
					</tr>
<? endforeach; ?>
				</tbody>
			</table>
<? $this->displayView('components/footer.php'); ?>