<?php use Rdlv\Fko\OvhEmailAliases\OvhEmailAliases; ?>
<?php if (isset($this) && $this instanceof OvhEmailAliases): ?>
    <?php global $title ?>
    <div class="wrap">
        <h1><?php echo $title ?></h1>

        <?php if (!empty($_REQUEST['error'])): ?>
            <div class="notice notice-error">
                <p>
                    <?php echo $_REQUEST['error'] ?>
                </p>
            </div>
        <?php endif ?>

        <h2><?php _e('Redirections', OvhEmailAliases::TEXTDOMAIN) ?></h2>
        
        <p>
            <?php _e('Une adresse email par ligne.', OvhEmailAliases::TEXTDOMAIN) ?>
        </p>

        <form method="post">
            <?php echo wp_nonce_field('ovh-email-aliases') ?>
            <table class="form-table">
                <?php $domains = $this->get_redirections() ?>
                <?php if (is_wp_error($domains)): ?>
                    <tr>
                        <td colspan="2" class="no-padding">
                            <?php $this->print_error($domains) ?>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($domains as $domain => $redirections): ?>
                        <?php if (is_wp_error($redirections)): ?>
                            <tr>
                                <td colspan="2" class="no-padding">
                                    <?php $this->print_error($redirections) ?>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php if (count($domains) > 1): ?>
                                <tr>
                                    <th><h3><?php echo $domain ?></h3></th>
                                    <td></td>
                                </tr>
                            <?php endif ?>
                            <tr>
                                <td class="no-padding">
                                    <label for="<?php echo 'redirection-' . $domain . '-new-from' ?>">
                                        <?php _e('nouvelle redirection de :', OvhEmailAliases::TEXTDOMAIN) ?>
                                    </label>
                                    <div class="one-line">
                                        <input class="regular-text code" type="text"
                                               name="<?php echo 'new[' . $domain . '][from]' ?>"
                                               id="<?php echo 'redirection-' . $domain . '-new-from' ?>">
                                        <span class="code">
                                           <?php echo '@' . $domain ?>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <label for="<?php echo 'redirection-' . $domain . '-new-to' ?>">
                                        <?php _e('vers :', OvhEmailAliases::TEXTDOMAIN) ?>
                                    </label><br>
                                    <textarea class="regular-text code"
                                              rows="2"
                                              name="<?php echo 'new[' . $domain . '][to]' ?>"
                                              id="<?php echo 'redirection-' . $domain . '-new-to' ?>"></textarea>
                                </td>
                            </tr>
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
                        <?php endif ?>
                    <?php endforeach ?>
                <?php endif ?>
            </table>
            <p class="submit">
                <input type="submit" value="<?php _e('Enregistrer', OvhEmailAliases::TEXTDOMAIN) ?>"
                       name="save" class="button-primary">
            </p>
        </form>

        <?php /*
        <p>
            <?php _e('Vous devez d’abord renseigner les paramètres ci-dessous avant de pouvoir gérer vos redirections', OvhEmailAliases::TEXTDOMAIN) ?>
        </p>
        */ ?>

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
                <tr>
                    <th scope="row">
                        <label for="domains"><?php _e('Domaines', OvhEmailAliases::TEXTDOMAIN) ?></label>
                    </th>
                    <td>
                        <?php $domains = $this->get_domains() ?>
                        <?php if (is_wp_error($domains)): ?>
                            <?php $this->print_error($domains) ?>
                        <?php elseif ($domains): ?>
                            <?php foreach ($domains as $domain => $checked): ?>
                                <p>
                                    <input type="checkbox"
                                           name="<?php echo sprintf(OvhEmailAliases::OPTION_FORMAT . '[%s]', 'domains', $domain) ?>"
                                        <?php if ($checked) echo 'checked="checked"' ?>
                                           id="domain-<?php echo $domain ?>"/>
                                    <label for="domain-<?php echo $domain ?>"><?php echo $domain ?></label>
                                </p>
                            <?php endforeach ?>
                        <?php endif ?>
                    </td>
                </tr>
                </tbody>
            </table>
            <p class="submit">
                <input type="submit" value="<?php _e('Enregistrer', OvhEmailAliases::TEXTDOMAIN) ?>"
                       name="save" class="button-primary">
            </p>
        </form>
    </div>
<?php endif ?>
