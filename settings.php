<?php use Rdlv\Fko\OvhEmailAliases\OvhEmailAliases; ?>
<?php if (isset($this) && $this instanceof OvhEmailAliases): ?>
    <?php global $title ?>
    <div class="wrap">
        <h1><?php echo $title ?></h1>

        <?php $message_list = $this->get_messages() ?>
        <?php if (!empty($message_list)): ?>
            <?php foreach ($message_list as $type => $messages): ?>
                <div class="notice notice-<?php echo $type ?>">
                    <?php foreach ($messages as $message): ?>
                        <p><?php echo $message ?></p>
                    <?php endforeach ?>
                </div>
            <?php endforeach ?>
        <?php endif ?>

        <?php $domains = $this->get_redirections() ?>
        <?php if (is_wp_error($domains)): ?>
            <?php $this->print_error($domains) ?>
        <?php elseif ($domains): ?>
            <form method="post">
                <?php if (count($domains) < 2): ?>
                    <h2><?php _e('Redirections', OvhEmailAliases::TEXTDOMAIN) ?></h2>
                <?php endif ?>
                <p>
                    <?php _e('Une adresse email par ligne.', OvhEmailAliases::TEXTDOMAIN) ?>
                </p>
                <?php echo wp_nonce_field('ovh-email-aliases') ?>
                <?php foreach ($domains as $domain => $redirections): ?>
                    <?php if (is_wp_error($redirections)): ?>
                        <?php $this->print_error($redirections) ?>
                    <?php else: ?>
                        <?php if (count($domains) > 1): ?>
                            <h2><?php echo $domain ?></h2>
                        <?php endif ?>
                        <table class="form-table">
                            <?php foreach ($redirections as $from => $to): ?>
                                <?php list($user) = explode('@', $from) ?>
                                <tr>
                                    <th scope="row">
                                        <label for="<?php echo 'redirection-' . $domain . '-' . $user ?>">
                                            <?php echo $from ?>
                                        </label>
                                    </th>
                                    <td>
                                <textarea class="regular-text code"
                                          rows="<?php echo max((count($to) + 1), 2) ?>"
                                          name="<?php echo 'aliases[' . $domain . '][' . $user . ']' ?>"
                                          id="<?php echo 'redirection-' . $domain . '-' . $user ?>"><?php
                                    foreach ($to as $address) {
                                        echo $address . "\n";
                                    }
                                    ?></textarea>
                                    </td>
                                </tr>
                            <?php endforeach ?>
                            <tr>
                                <th scope="row">
                                    <?php _e('nouvelle redirection', OvhEmailAliases::TEXTDOMAIN) ?>
                                </th>
                                <td>
                                    <label for="<?php echo 'redirection-' . $domain . '-new-from' ?>">
                                        <?php _e('de', OvhEmailAliases::TEXTDOMAIN) ?>
                                    </label>
                                    <p class="new-from">
                                        <input class="regular-text code" type="text"
                                               name="<?php echo 'new[' . $domain . '][from]' ?>"
                                               id="<?php echo 'redirection-' . $domain . '-new-from' ?>">
                                        <span class="code">
                                           <?php echo '@' . $domain ?>
                                        </span>
                                    </p>
                                    <label for="<?php echo 'redirection-' . $domain . '-new-to' ?>">
                                        <?php _e('vers', OvhEmailAliases::TEXTDOMAIN) ?>
                                    </label><br>
                                    <textarea class="regular-text code"
                                              rows="2"
                                              name="<?php echo 'new[' . $domain . '][to]' ?>"
                                              id="<?php echo 'redirection-' . $domain . '-new-to' ?>"></textarea>
                                </td>
                            </tr>
                        </table>
                    <?php endif ?>
                <?php endforeach ?>
                <p class="submit">
                    <input type="submit" value="<?php _e('Enregistrer', OvhEmailAliases::TEXTDOMAIN) ?>"
                           name="save" class="button-primary">
                </p>
            </form>
        <?php else: ?>
            <h2><?php _e('Redirections', OvhEmailAliases::TEXTDOMAIN) ?></h2>
            <p>
                <?php _e('Vous devez d’abord renseigner les paramètres ci-dessous avant de pouvoir gérer vos redirections.', OvhEmailAliases::TEXTDOMAIN) ?>
            </p>
        <?php endif ?>

        <h2><?php _e('Paramètres d’API', OvhEmailAliases::TEXTDOMAIN) ?></h2>

        <p>
            <?php _e(sprintf(
                'Si vous ne possédez pas de clés d’API, vous pouvez les <a href="%s" target="_blank">générer sur OVH</a>.<br>Vous aurez besoin des identifiant et mot de passe de votre compte OVH.',
                OvhEmailAliases::CREATE_APP_URL
            ), OvhEmailAliases::TEXTDOMAIN) ?>
        </p>

        <!--suppress HtmlUnknownTarget -->
        <form method="post" action="options.php">
            <?php settings_fields('ovh-email-aliases'); ?>
            <table class="form-table">
                <tbody>
                <tr>
                    <th scope="row">
                        <label for="api-key"><?php _e('Clé d’API', OvhEmailAliases::TEXTDOMAIN) ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text code"
                               value="<?php echo get_option(sprintf(OvhEmailAliases::OPTION_FORMAT, 'api_key')) ?>"
                               name="<?php printf(OvhEmailAliases::OPTION_FORMAT, 'api_key') ?>"
                               id="api-key">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api-secret"><?php _e('Secret d’API', OvhEmailAliases::TEXTDOMAIN) ?></label>
                    </th>
                    <td>
                        <input type="text" class="regular-text code"
                               value="<?php echo get_option(sprintf(OvhEmailAliases::OPTION_FORMAT, 'api_secret')) ?>"
                               name="<?php printf(OvhEmailAliases::OPTION_FORMAT, 'api_secret') ?>"
                               id="api-secret">
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="api-token"><?php _e('Jeton d’API', OvhEmailAliases::TEXTDOMAIN) ?></label>
                    </th>
                    <td>
                        <input type="text" disabled="disabled" class="regular-text code"
                               value="<?php echo get_option(sprintf(OvhEmailAliases::OPTION_FORMAT, 'api_token')) ?>"
                               id="api-secret">
                        <button type="submit" name="get_token" class="button">
                            <?php _e('Générer', OvhEmailAliases::TEXTDOMAIN) ?>
                        </button>
                    </td>
                </tr>
                <?php $domains = $this->get_domains() ?>
                <?php if (is_wp_error($domains)): ?>
                    <tr>
                        <td colspan="2" class="no-padding">
                            <?php $this->print_error($domains) ?>
                        </td>
                    </tr>
                <?php elseif ($domains): ?>
                    <tr>
                        <th scope="row">
                            <label for="domains"><?php _e('Domaines', OvhEmailAliases::TEXTDOMAIN) ?></label>
                        </th>
                        <td>
                            <?php foreach ($domains as $domain => $checked): ?>
                                <p>
                                    <input type="checkbox"
                                           name="<?php echo sprintf(OvhEmailAliases::OPTION_FORMAT . '[%s]', 'domains', $domain) ?>"
                                        <?php if ($checked) echo 'checked="checked"' ?>
                                           id="domain-<?php echo $domain ?>"/>
                                    <label for="domain-<?php echo $domain ?>"><?php echo $domain ?></label>
                                </p>
                            <?php endforeach ?>
                        </td>
                    </tr>
                <?php endif ?>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" value="<?php _e('Enregistrer', OvhEmailAliases::TEXTDOMAIN) ?>"
                       name="save" class="button-primary">
            </p>
        </form>
    </div>
<?php endif ?>
