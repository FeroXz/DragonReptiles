<?php
return [
    'github_owner'   => getenv('GITHUB_OWNER')   ?: 'FeroXz',
    'github_repo'    => getenv('GITHUB_REPO')    ?: 'Dragon-Reptiles',
    'sftp_host'      => getenv('SFTP_HOST')      ?: '',
    'sftp_port'      => (int) (getenv('SFTP_PORT') ?: 22),
    'sftp_user'      => getenv('SFTP_USER')      ?: '',
    'sftp_pass'      => getenv('SFTP_PASS')      ?: '',
    'sftp_key_b64'   => getenv('SFTP_KEY_BASE64') ?: '',
    'target_dir'     => getenv('DEPLOY_TARGET_DIR') ?: '/cms',
    'default_dry_run'=> (bool) getenv('DRY_RUN'),
];
