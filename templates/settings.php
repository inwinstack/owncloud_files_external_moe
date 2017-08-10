<?php
        OC_Util::addScript('files_external_moe','help');
	use \OCA\Files_External_MOE\Lib\Backend\Backend;
	use \OCA\Files_External_MOE\Lib\DefinitionParameter;
	use \OCA\Files_External_MOE\Service\BackendService;

	function writeParameterInput($parameter, $options, $classes = []) {
		$value = '';
		if (isset($options[$parameter->getName()])) {
			$value = $options[$parameter->getName()];
		}
		$placeholder = $parameter->getText();
		$is_optional = $parameter->isFlagSet(DefinitionParameter::FLAG_OPTIONAL);

		switch ($parameter->getType()) {
		case DefinitionParameter::VALUE_PASSWORD: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="password"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
			break;
		case DefinitionParameter::VALUE_BOOLEAN: ?>
			<?php $checkboxId = uniqid("checkbox_"); ?>
			<input type="checkbox"
				id="<?php p($checkboxId); ?>"
				<?php if (!empty($classes)): ?> class="checkbox <?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				<?php if ($value === true): ?> checked="checked"<?php endif; ?>
			/>
			<label for="<?php p($checkboxId); ?>"><?php p($placeholder); ?></label>
			<?php
			break;
		case DefinitionParameter::VALUE_HIDDEN: ?>
			<input type="hidden"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
			/>
			<?php
			break;
		default: ?>
			<?php if ($is_optional) { $classes[] = 'optional'; } ?>
			<input type="text"
				<?php if (!empty($classes)): ?> class="<?php p(implode(' ', $classes)); ?>"<?php endif; ?>
				data-parameter="<?php p($parameter->getName()); ?>"
				value="<?php p($value); ?>"
				placeholder="<?php p($placeholder); ?>"
			/>
			<?php
		}
	}
?>
<?php if(\OC::$server->getAppManager()->isEnabledForUser('files_external_moe')):?>
    <form id="files_external" class="section" data-encryption-enabled="<?php echo $_['encryptionEnabled']?'true': 'false'; ?>">
    	<h2><?php p($l->t('External Storage')); ?></h2>
        <a class="files_external_info svg" title="" data-original-title=<?php p($l->t('Help')); ?>></a>
    	<?php if (isset($_['dependencies']) and ($_['dependencies']<>'')) print_unescaped(''.$_['dependencies'].''); ?>
    
    	<?php if (!$_['isAdminPage']): ?>
    	<p>
    	    <?php p($l->t('After you mount the external cloud personal space and allow this service to access it, you can access the file in this space. If ')); ?>
    	    <span class="successful"></span>
    	    <?php p($l->t(' is displayed, the connection is successful. If ')); ?>
    	    <span class="failed"></span>
    	    <?php p($l->t(' is displayed, it indicates that the connection fails. If the connection fails, please click the Reconnect button.')); ?>
            </p>
    	<br>
    	<p> 
    	    <?php p($l->t('You can mount the following external cloud personal spaces:')); ?>
    	    <button class="externalBtn">GoogleDrive</button>
    	    <button class="externalBtn">Dropbox</button>
    <!-- 	    <button class="externalBtn">OneDrive</button> -->
    	</p>
    	<br>
    
    	<table id="externalStorage" class="grid" data-admin='<?php print_unescaped(json_encode($_['isAdminPage'])); ?>'>
                <?php if(!empty($_['storages'])): ?>
    		<thead>
    			<tr>
    				<th></th>
    				<th><?php p($l->t('Folder path')); ?></th>
    				<th><?php p($l->t('External storage')); ?></th>
    				<th class="hidden"><?php p($l->t('Authentication')); ?></th>
    				<th class="hidden"><?php p($l->t('Configuration')); ?></th>
    				<?php if ($_['isAdminPage']) print_unescaped('<th>'.$l->t('Available for').'</th>'); ?>
    				<th>&nbsp;</th>
    				<th>&nbsp;</th>
    			</tr>
    		</thead>
                <?php endif;?>
    		<tbody>
    		<?php foreach ($_['storages'] as $storage): ?>
    			<tr class="<?php p($storage->getBackend()->getIdentifier()); ?>" data-id="<?php p($storage->getId()); ?>">
    				<td class="status">
    					<span></span>
    				</td>
    				<td class="mountPoint"><input type="text" name="mountPoint"
    											  value="<?php p(ltrim($storage->getMountPoint(), '/')); ?>"
    											  data-mountpoint="<?php p(ltrim($storage->getMountPoint(), '/')); ?>"
    											  placeholder="<?php p($l->t('Folder name')); ?>" />
    				</td>
    				<td class="backend" data-class="<?php p($storage->getBackend()->getIdentifier()); ?>"><?php p($storage->getBackend()->getText()); ?>
    				</td>
    				<td class="authentication">
    					<select class="selectAuthMechanism">
    						<?php
    							$authSchemes = $storage->getBackend()->getAuthSchemes();
    							$authMechanisms = array_filter($_['authMechanisms'], function($mech) use ($authSchemes) {
    								return isset($authSchemes[$mech->getScheme()]);
    							});
    						?>
    						<?php foreach ($authMechanisms as $mech): ?>
    							<option value="<?php p($mech->getIdentifier()); ?>" data-scheme="<?php p($mech->getScheme());?>"
    								<?php if ($mech->getIdentifier() === $storage->getAuthMechanism()->getIdentifier()): ?>selected<?php endif; ?>
    							><?php p($mech->getText()); ?></option>
    						<?php endforeach; ?>
    					</select>
    				</td>
    				<td class="configuration">
    					<?php
    						$options = $storage->getBackendOptions();
    						foreach ($storage->getBackend()->getParameters() as $parameter) {
    							writeParameterInput($parameter, $options);
    						}
    						foreach ($storage->getAuthMechanism()->getParameters() as $parameter) {
    							writeParameterInput($parameter, $options, ['auth-param']);
    						}
    					?>
    				</td>
    				<?php if ($_['isAdminPage']): ?>
    					<td class="applicable"
    						align="right"
    						data-applicable-groups='<?php print_unescaped(json_encode($storage->getApplicableGroups())); ?>'
    						data-applicable-users='<?php print_unescaped(json_encode($storage->getApplicableUsers())); ?>'>
    						<input type="hidden" class="applicableUsers" style="width:20em;" value=""/>
    					</td>
    				<?php endif; ?>
    				<td class="mountOptionsToggle">
    					<img
    						class="svg action"
    						title="<?php p($l->t('Advanced settings')); ?>"
    						alt="<?php p($l->t('Advanced settings')); ?>"
    						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
    					/>
    					<input type="hidden" class="mountOptions" value="<?php p(json_encode($storage->getMountOptions())); ?>" />
    					<?php if ($_['isAdminPage']): ?>
    						<input type="hidden" class="priority" value="<?php p($storage->getPriority()); ?>" />
    					<?php endif; ?>
    				</td>
    				<td class="remove">
    					<img alt="<?php p($l->t('Delete')); ?>"
    						title="<?php p($l->t('Delete')); ?>"
    						class="svg action"
    						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
    					/>
    				</td>
    			</tr>
    		<?php endforeach; ?>
    			<tr id="addMountPoint">
    				<td class="status">
    					<span></span>
    				</td>
    				<td class="mountPoint"><input type="text" name="mountPoint" value=""
    					placeholder="<?php p($l->t('Folder name')); ?>">
    				</td>
    				<td class="backend">
    					<select id="selectBackend" class="selectBackend" data-configurations='<?php p(json_encode($_['backends'])); ?>'>
    						<option value="" disabled selected
    							style="display:none;">
    							<?php p($l->t('Add storage')); ?>
    						</option>
    						<?php
    							$sortedBackends = $_['backends'];
    							uasort($sortedBackends, function($a, $b) {
    								return strcasecmp($a->getText(), $b->getText());
    							});
    						?>
    						<?php foreach ($sortedBackends as $backend): ?>
    							<?php if ($backend->getDeprecateTo()) continue; // ignore deprecated backends ?>
    							<option value="<?php p($backend->getIdentifier()); ?>"><?php p($backend->getText()); ?></option>
    						<?php endforeach; ?>
    					</select>
    				</td>
    				<td class="authentication" data-mechanisms='<?php p(json_encode($_['authMechanisms'])); ?>'></td>
    				<td class="configuration"></td>
    				<?php if ($_['isAdminPage']): ?>
    					<td class="applicable" align="right">
    						<input type="hidden" class="applicableUsers" style="width:20em;" value="" />
    					</td>
    				<?php endif; ?>
    				<td class="mountOptionsToggle hidden">
    					<img class="svg action"
    						title="<?php p($l->t('Advanced settings')); ?>"
    						alt="<?php p($l->t('Advanced settings')); ?>"
    						src="<?php print_unescaped(image_path('core', 'actions/settings.svg')); ?>"
    					/>
    					<input type="hidden" class="mountOptions" value="" />
    				</td>
    				<td class="hidden">
    					<img class="svg action"
    						alt="<?php p($l->t('Delete')); ?>"
    						title="<?php p($l->t('Delete')); ?>"
    						src="<?php print_unescaped(image_path('core', 'actions/delete.svg')); ?>"
    					/>
    				</td>
    			</tr>
    		</tbody>
    	</table>
    	<br />
    
    	<?php endif; ?>
    	<?php if ($_['isAdminPage']): ?>
    		<br />
    		<input type="checkbox" name="allowUserMounting" id="allowUserMounting" class="checkbox"
    			value="1" <?php if ($_['allowUserMounting'] == 'yes') print_unescaped(' checked="checked"'); ?> />
    		<label for="allowUserMounting"><?php p($l->t('Enable User External Storage')); ?></label> <span id="userMountingMsg" class="msg"></span>
    
    		<p id="userMountingBackends"<?php if ($_['allowUserMounting'] != 'yes'): ?> class="hidden"<?php endif; ?>>
    			<?php p($l->t('Allow users to mount the following external storage')); ?><br />
    			<?php $i = 0; foreach ($_['userBackends'] as $backend): ?>
    				<?php if ($deprecateTo = $backend->getDeprecateTo()): ?>
    					<input type="hidden" id="allowUserMountingBackends<?php p($i); ?>" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" data-deprecate-to="<?php p($deprecateTo->getIdentifier()); ?>" />
    				<?php else: ?>
    					<input type="checkbox" id="allowUserMountingBackends<?php p($i); ?>" class="checkbox" name="allowUserMountingBackends[]" value="<?php p($backend->getIdentifier()); ?>" <?php if ($backend->isVisibleFor(BackendService::VISIBILITY_PERSONAL)) print_unescaped(' checked="checked"'); ?> />
    					<label for="allowUserMountingBackends<?php p($i); ?>"><?php p($backend->getText()); ?></label> <br />
    				<?php endif; ?>
    				<?php $i++; ?>
    			<?php endforeach; ?>
    		</p>
    	<?php endif; ?>
    </form>
<?php endif; ?>
