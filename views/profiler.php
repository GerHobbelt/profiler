<!-- ANBU - LARAVEL PROFILER -->
<style type="text/css"><?php echo file_get_contents($assetPath.'profiler.css'); ?></style>
<div class="anbu">
	<div class="anbu-window">
		<div class="anbu-content-area">
			<div class="anbu-tab-pane anbu-table anbu-environment">
				<table>
					<tr>
						<th>Key</th>
						<th>Value</th>
					</tr>
					<tr>
						<td>Timezone</td>
						<td><?php echo Config::get('app.timezone') ?></td>
					</tr>
					<tr>
						<td>Locale</td>
						<td><?php echo Config::get('app.locale') ?></td>
					</tr>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-controller">
				<table>
					<tr>
						<th>Key</th>
						<th>Value</th>
					</tr>
					<tr>
						<td>Current route</td>
						<td><?php echo Route::currentRouteName() ?></td>
					</tr>
					<tr>
						<td>Current controller action</td>
						<td><?php echo Route::currentRouteAction() ?></td>
					</tr>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-log">
				<?php if(count($logger->getLogs()) > 0): ?>
					<table>
						<tr>
							<th>Type</th>
							<th>Message</th>
						</tr>
						<?php foreach($logger->getLogs() as $log): ?>
							<tr>
								<td class="anbu-table-first">
									<?php echo $log['level']; ?>
								</td>
								<td>
									<?php echo $log['message']; ?>
								</td>
						<?php endforeach; ?>
						</tr>
					</table>
				<?php else: ?>
					<span class="anbu-empty">There are no log entries.</span>
				<?php endif; ?>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-checkpoints">
				<table>
					<tr>
						<th>Name</th>
						<th>Running Time (ms)</th>
					</tr>
					<?php foreach($profiler->getTimers() as $name => $timer): ?>
					<tr>
						<td class="anbu-table-first">
							<?php echo $name; ?>
						</td>
						<td><pre><?php echo $timer->getElapsedTime(); ?>ms</pre></td>
						<td>&nbsp;</td>
					</tr>

					<?php endforeach; ?>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-routes">
				<table>
				    <tr>
				        <th>Routes</th>
				    </tr>
					<?php $routes = Route::getRoutes(); ?>
				    <?php foreach ($routes as $name => $route): ?>
						<tr>
							<td><?php echo $name ?></td>
						</tr>
				    <?php endforeach ?>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-services">
				<table>
				    <tr>
				        <th>Service Providers</th>
				    </tr>
					<tr>
						<td><pre><?php echo var_export(json_decode(file_get_contents(storage_path() . '/meta/services.json'))) ?></pre></td>
					</tr>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-sql">
				<?php if (count($logger->getQueries()) > 0): ?>
					<table>
						<tr>
							<th>Time</th>
							<th>Query</th>
						</tr>
						<?php foreach($logger->getQueries() as $query): ?>
							<tr>
								<td class="anbu-table-first">
									<?php echo $query['time']; ?>ms
								</td>
								<td>
									<pre><?php echo $query['query']; ?></pre>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php else: ?>
					<span class="anbu-empty">There have been no SQL queries executed.</span>
				<?php endif; ?>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-filecount">
				<table>
					<tr>
						<th>File</th>
						<th>Size</th>
					</tr>
					<?php foreach($profiler->getIncludedFiles() as $file): ?>
					<tr>
						<td class="anbu-table-first-wide"><?php echo $file['filePath']; ?></td>
						<td><pre><?php echo $file['size']?></pre></td>
						<td>&nbsp;</td>
					</tr>

					<?php endforeach; ?>
				</table>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-session">
				<?php $session = Session::all() ?>

				<?php if ( ! empty($session)): ?>
					<table>
						<tr>
							<th>Key</th>
							<th>Value</th>
						</tr>
						<?php foreach ($session as $key => $value): ?>
							<tr>
								<td><?php echo $key ?></td>
								<td>
									<?php if (is_array($value)): ?>
										<pre><?php echo print_r($value, true) ?></pre>
									<?php else: ?>
										<?php echo $value ?>
									<?php endif ?>
								</td>
							</tr>
						<?php endforeach; ?>
					</table>
				<?php else: ?>
					<span class="anbu-empty">There are no session entries.</span>
				<?php endif ?>
			</div>

			<div class="anbu-tab-pane anbu-table anbu-view">
				<table>
					<tr>
						<th>Variable</th>
						<th>Value</th>
					</tr>
					<?php //$view_data = $profiler::getViewData() ?>
					<?php foreach($view_data as $key => $value): ?>
						<tr>
							<td><?php echo $key ?></td>
							<?php if(is_array($value)): ?>
								<?php $value = Profiler::cleanArray($value) ?>
								<td><pre><?php echo print_r($value, true) ?></pre></td>
							<?php else: ?>
								<td><?php echo $value ?></td>
							<?php endif ?>
						</tr>
					<?php endforeach ?>
				</table>
			</div>
		</div>
	</div>

	<ul id="anbu-open-tabs" class="anbu-tabs">
		<li><a data-anbu-tab="anbu-environment" class="anbu-tab" href="#">Env <span class="anbu-count"><?php echo App::environment() ?></span></a></li>
		<li><a data-anbu-tab="anbu-controller" class="anbu-tab" href="#">Controller <span class="anbu-count"><?php echo Route::currentRouteAction() ?></span></a></li>
		<li><a data-anbu-tab="anbu-log" class="anbu-tab" href="#">Log <span class="anbu-count"><?php echo count($logger->getLogs()); ?></span></a></li>
		<li><a class="anbu-tab" data-anbu-tab="anbu-checkpoints">Time <span class="anbu-count"><?php echo round($profiler->getLoadTime() / 1000, 3); ?> sec</span></a></li>
		<li><a class="anbu-tab">Memory <span class="anbu-count"><?php echo $profiler->getMemoryUsage(); ?> (<?php echo $profiler->getMemoryPeak(); ?>)</span></a></li>
		<br>
		<li><a data-anbu-tab="anbu-routes" class="anbu-tab" href="#">Routes <span class="anbu-count"><?php echo count(Route::getRoutes()) ?></span></a></li>
		<li><a data-anbu-tab="anbu-services" class="anbu-tab" href="#">Service Providers</a></li>
		<li>
			<a data-anbu-tab="anbu-sql" class="anbu-tab" href="#">SQL
				<span class="anbu-count"><?php echo count($logger->getQueries()); ?></span>
				<?php if(count($logger->getQueries()) > 0): ?>
					<span class="anbu-count"><?php echo array_sum(array_map(function($q) { return $q['time']; }, $logger->getQueries())); ?>ms</span>
				<?php endif; ?>
			</a>
		</li>
		<li><a class="anbu-tab" data-anbu-tab="anbu-filecount">Files <span class="anbu-count"><?php echo count($profiler->getIncludedFiles()); ?></span></a></li>
		<li><a class="anbu-tab" data-anbu-tab="anbu-session">Session <span class="anbu-count"><?php echo count(Session::all()); ?></span></a></li>
		<li><a class="anbu-tab" data-anbu-tab="anbu-view">View <span class="anbu-count"><?php echo count($view_data) ?></span></a></li>
		<li class="anbu-tab-right"><a id="anbu-hide" href="#">&#8614;</a></li>
		<li class="anbu-tab-right"><a id="anbu-close" href="#">&times;</a></li>
		<li class="anbu-tab-right"><a id="anbu-zoom" href="#">&#8645;</a></li>
	</ul>

	<ul id="anbu-closed-tabs" class="anbu-tabs">
		<li><a id="anbu-show" href="#">&#8612;</a></li>
	</ul>
</div>

<script src='//ajax.googleapis.com/ajax/libs/jquery/1.7.2/jquery.min.js'></script>
<script><?php echo file_get_contents($assetPath.'profiler.js'); ?></script>
<!-- /ANBU - LARAVEL PROFILER -->
