<?php
use CommunityTranslation\Glossary\EntryType as GlossaryEntryType;

/* @var Concrete\Core\View\View $view */
/* @var Concrete\Package\CommunityTranslation\Controller\Frontend\OnlineTranslation $controllers */

// Arguments
/* @var CommunityTranslation\Entity\Package\Version|string $packageVersion */
/* @var Concrete\Core\Validation\CSRF\Token $token */
/* @var bool $canApprove */
/* @var CommunityTranslation\Entity\Locale $locale */
/* @var bool $canEditGlossary */
/* @var array $pluralCases */
/* @var array $translations */
/* @var string $pageTitle */
/* @var array $allVersions */
/* @var array $allLocales */
/* @var string $onlineTranslationPath */
/* @var array $translationFormats */
/* @var string|null $showDialogAtStartup */

?><!DOCTYPE html>
<html lang="<?= Localization::activeLanguage() ?>">
<head>
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <?php View::element('header_required', ['pageTitle' => $pageTitle]); ?>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script>
        if (navigator.userAgent.match(/IEMobile\/10\.0/)) {
            var msViewportStyle = document.createElement('style')
            msViewportStyle.appendChild(
                document.createTextNode(
                    '@-ms-viewport{width:auto!important}'
                )
            )
            document.querySelector('head').appendChild(msViewportStyle)
        }
    </script>
</head><body>
<nav class="navbar navbar-inverse">
    <div class="container-fluid">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand" href="<?= URL::to('/') ?>"><?= h($pageTitle) ?></a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
            <ul class="nav navbar-nav navbar-right">
                <?php
                if ($canApprove && is_object($packageVersion)) {
                    ?><li><a href="<?= URL::to($onlineTranslationPath, 'unreviewed', $locale->getID()) ?>" title="<?= t('View all strings to be reviewed') ?>"><i class="fa fa-thumbs-up"></i></a></li><?php
                }
                ?>
                <li><a href="#" data-toggle="modal" data-target="#comtra_translation-upload" title="<?= t('Upload translations') ?>"><i class="fa fa-cloud-upload"></i></a>
                <li><a href="#" data-toggle="modal" data-target="#comtra_translation-download" title="<?= t('Download translations') ?>"><i class="fa fa-cloud-download"></i></a>
            </ul>
            <?php
            if (!empty($allVersions) || !empty($allLocales)) {
                ?>
                <form class="navbar-form navbar-right" onsubmit="return false">
                    <?php
                    if (!empty($allVersions)) {
                        ?>
                        <select class="form-control" onchange="if (this.value) { window.location.href = this.value; this.disabled = true; }">
                            <?php
                            foreach ($allVersions as $u => $n) {
                                ?><option value="<?= h($u) ?>"<?= ($u === '') ? ' selected="selected"' : ''?>><?= h($n) ?></option><?php
                            }
                            ?>
                        </select>
                        <?php
                    }
                    if (!empty($allLocales)) {
                        ?>
                        <select class="form-control" onchange="if (this.value) { window.location.href = this.value; this.disabled = true; }">
                            <?php
                            foreach ($allLocales as $u => $n) {
                                ?><option value="<?= h($u) ?>"<?= ($u === '') ? ' selected="selected"' : ''?>><?= h($n) ?></option><?php
                            }
                            ?>
                        </select>
                        <?php
                    }
                    ?>
                </form>
                <?php
            }
            ?>
        </div>
    </div>
</nav>

<div class="container-fluid" id="comtra_translator"></div>

<div id="comtra_extra-references" class="col-md-12">
    <div class="panel panel-primary">
        <div class="panel-heading"><?= t('References') ?></div>
        <div class="panel-body" id="comtra_translation-references">
            <div class="alert alert-info comtra_none">
                <?= t('No references found for this string.') ?>
            </div>
            <div class="comtra_some"></div>
            <div style="text-align: right; margin-top: 20px">
                <a href="#" class="btn btn-primary btn-sm" id="comtra_translation-references-showallplaces"><?= t('Show all the places where this string is used') ?></a>
            </div>
        </div>
    </div>
</div>

<div id="comtra_extra-tabs" class="col-md-5">
    <ul class="nav nav-tabs">
        <li class="active"><a href="#comtra_translation-others" role="tab" data-toggle="tab"><?= t('Other translations') ?> <span class="badge" id="comtra_translation-others-count"></span></a></li>
        <li><a href="#comtra_translation-comments" role="tab" data-toggle="tab"><?= t('Comments') ?> <span class="badge" id="comtra_translation-comments-count"></span></a></li>
        <li><a href="#comtra_translation-suggestions" role="tab" data-toggle="tab"><?= t('Suggestions') ?> <span class="badge" id="comtra_translation-suggestions-count"></span></a></li>
        <li><a href="#comtra_translation-glossary" role="tab" data-toggle="tab"><?= t('Glossary') ?> <span class="badge" id="comtra_translation-glossary-count"></span></a></li>
    </ul>
    <div class="tab-content">
        <div role="tabpanel" class="tab-pane active" role="tabpanel" id="comtra_translation-others">
            <div class="alert alert-info comtra_none">
                <?= t('No other translations found.') ?>
            </div>
            <table class="comtra_some table table-striped">
                <thead>
                    <tr>
                        <th style="width: 1px"><?= t('Date') ?></th>
                        <th><?= t('Translation') ?></th>
                        <th style="width: 1px"><?= t('Action') ?></th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
        <div role="tabpanel" class="tab-pane" role="tabpanel" id="comtra_translation-comments">
            <div class="alert alert-info comtra_none">
                <?= t('No comments found for this string.') ?>
            </div>
            <div class="list-group" id="comtra_translation-comments-extracted"><div class="list-group-item active"><?= t('Extracted comments') ?></div></div>
            <div class="list-group" id="comtra_translation-comments-online"><div class="list-group-item active"><?= t('Translators comments') ?></div></div>
            <div style="text-align: right">
                <a href="#" class="btn btn-primary btn-sm" id="comtra_translation-comments-add"><?= t('New comment') ?></a>
            </div>
        </div>
        <div role="tabpanel" class="tab-pane" role="tabpanel" id="comtra_translation-suggestions">
            <div class="alert alert-info comtra_none">
                <?= t('No similar translations found for this string.') ?>
            </div>
            <div class="comtra_some list-group"><div class="list-group-item active"><?= t('Similar translations') ?></div></div>
        </div>
        <div role="tabpanel" class="tab-pane" role="tabpanel" id="comtra_translation-glossary">
            <div class="alert alert-info comtra_none">
                <?= t('No glossary terms found for this string.') ?>
            </div>
            <dl class="comtra_some dl-horizontal"></dl>
            <?php
            if ($canEditGlossary) {
                ?>
                <div style="text-align: right">
                    <a href="#" class="btn btn-primary btn-sm" id="comtra_translation-glossary-add"><?= t('New term') ?></a>
                </div>
                <?php
            }
            ?>
        </div>
    </div>
</div>

<div id="comtra_translation-comments-dialog" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= t('Translation comment') ?></h4>
            </div>
            <div class="modal-body">
                <form onsubmit="return false">
                    <div class="form-group" id="comtra_editcomment-visibility">
                        <label><?= t('Comment visibility') ?></label>
                        <div class="radio">
                            <label>
                                <input type="radio" name="comtra_editcomment-visibility" value="locale" />
                                <?= t('This is a comment only for %s', $locale->getDisplayName()) ?>
                            </label>
                        </div>
                        <div class="radio">
                            <label>
                                <input type="radio" name="comtra_editcomment-visibility" value="global" />
                                <?= t('This is a comment for all languages') ?>
                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="pull-right small"><a href="http://commonmark.org/help/" target="_blank"><?= t('Markdown syntax') ?></a></div>
                        <label for="comtra_editcomment"><?= t('Comment') ?></label>
                        <textarea class="form-control" id="comtra_editcomment"></textarea>
                    </div>
                    <div class="form-group">
                        <label for="comtra_editcomment_render"><?= t('Preview') ?></label>
                        <div id="comtra_editcomment_render"></div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal"><?= t('Cancel') ?></button>
                <button type="button" class="btn btn-danger" id="comtra_translation-glossary-delete"><?= t('Delete') ?></button>
                <button type="button" class="btn btn-primary"><?= t('Save') ?></button>
            </div>
        </div>
    </div>
</div>

<div id="comtra_allplaces-dialog" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                <h4 class="modal-title"><?= t('String usage') ?></h4>
            </div>
            <div class="modal-body">
                <div class="list-group"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-dismiss="modal"><?= t('Close') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
if ($canEditGlossary) {
    ?>
    <div id="comtra_translation-glossary-dialog" class="modal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><?= t('Glossary Term') ?></h4>
                </div>
                <div class="modal-body">
                    <form class="form-horizontal" onsubmit="return false">
                        <fieldset>
                            <legend><?= t('Info shared with all languages') ?></legend>
                            <div class="form-group">
                                <label for="comtra_gloentry_term" class="col-sm-2 control-label"><?= t('Term') ?></label>
                                <div class="col-sm-10">
                                    <div class="input-group">
                                        <input type="text" class="form-control" id="comtra_gloentry_term" maxlength="255" />
                                        <span class="input-group-addon" id="basic-addon2">*</span>
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comtra_gloentry_type" class="col-sm-2 control-label"><?= t('Type') ?></label>
                                <div class="col-sm-10">
                                    <select class="form-control" id="comtra_gloentry_type">
                                        <option value=""><?= tc('Type', 'none') ?></option>
                                        <?php
                                        foreach (GlossaryEntryType::getTypesInfo() as $typeHandle => $typeInfo) {
                                            ?><option value="<?= h($typeHandle) ?>"><?= h($typeInfo['name']) ?></option><?php
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comtra_gloentry_termComments" class="col-sm-2 control-label"><?= t('Comments') ?></label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="comtra_gloentry_termComments" style="resize: vertical"></textarea>
                                </div>
                            </div>
                        </fieldset>
                        <fieldset>
                            <legend><?= t('Info only for %s', $locale->getDisplayName()) ?></legend>
                            <div class="form-group">
                                <label for="comtra_gloentry_translation" class="col-sm-2 control-label"><?= t('Translation') ?></label>
                                <div class="col-sm-10">
                                    <input type="text" class="form-control" id="comtra_gloentry_translation" />
                                </div>
                            </div>
                            <div class="form-group">
                                <label for="comtra_gloentry_translationComments" class="col-sm-2 control-label"><?= t('Comments') ?></label>
                                <div class="col-sm-10">
                                    <textarea class="form-control" id="comtra_gloentry_translationComments" style="resize: vertical"></textarea>
                                </div>
                            </div>
                        </fieldset>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= t('Cancel') ?></button>
                    <button type="button" class="btn btn-danger" id="comtra_translation-glossary-delete"><?= t('Delete') ?></button>
                    <button type="button" class="btn btn-primary"><?= t('Save') ?></button>
                </div>
            </div>
        </div>
    </div>
<?php
} ?>

<div id="comtra_translation-upload" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" enctype="multipart/form-data" action="<?= $this->action('upload', $locale->getID()) ?>" onsubmit="if (this.already) return; this.already = true">
                <?php $token->output('comtra-upload-translations' . $locale->getID()); ?>
                <input type="hidden" name="packageVersion" value="<?= is_object($packageVersion) ? $packageVersion->getID() : h($packageVersion) ?>" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><?= t('Upload translations') ?></h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label class="control-label" for="comtra_upload-translations-file"><?= t('File to be imported') ?></label>
                        <input class="form-control" type="file" name="file" id="comtra_upload-translations-file" required="required" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= t('Cancel') ?></button>
                    <input type="submit" class="btn btn-primary" value="<?= t('Upload') ?>" />
                </div>
            </form>
        </div>
    </div>
</div>

<div id="comtra_translation-download" class="modal">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="<?= $this->action('download', $locale->getID()) ?>" target="_blank">
                <?php $token->output('comtra-download-translations' . $locale->getID()); ?>
                <input type="hidden" name="packageVersion" value="<?= is_object($packageVersion) ? $packageVersion->getID() : h($packageVersion) ?>" />
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                    <h4 class="modal-title"><?= t('Download translations') ?></h4>
                </div>
                <div class="modal-body">
                    <fieldset>
                        <legend><?= t('File format') ?></legend>
                        <?php
                        foreach ($translationFormats as $f => $n) {
                            ?>
                            <div class="radio">
                                <label>
                                    <input type="radio" name="download-format" value="<?= h($f) ?>" required="required" />
                                    <?= h($n) ?>
                                </label>
                            </div>
                            <?php
                        }
                        ?>
                    </fieldset>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal"><?= t('Close') ?></button>
                    <input type="submit" class="btn btn-primary" value="<?= t('Download') ?>" />
                </div>
            </form>
        </div>
    </div>
</div>

<?php
if (isset($showDialogAtStartup)) {
    ?>
    <div class="modal fade" role="dialog" id="comtra_startup-dialog">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title"><?= t('Message') ?></h4>
                </div>
                <div class="modal-body">
                    <?= $showDialogAtStartup ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" data-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script>
        $(document).ready(function() {
            $('#comtra_startup-dialog')
                .modal()
                .on('hidden.bs.modal', function() {
                    $('#comtra_startup-dialog').remove();
                })
            ;
        });
    </script>
    <?php
}
?>

<script>$(document).ready(function() {

window.ccmTranslator.configureFrontend({
    colOriginal: 'col-md-3',
    colTranslations: 'col-md-12'
});
window.comtraOnlineEditorInitialize(<?php
$params = [
    'packageVersionID' => is_object($packageVersion) ? $packageVersion->getID() : null,
    'canApprove' => $canApprove,
    'pluralRuleByIndex' => array_keys($pluralCases),
    'plurals' => $pluralCases,
    'translations' => $translations,
    'canEditGlossary' => $canEditGlossary,
    'actions' => [
        'saveComment' => (string) $this->action('save_comment', $locale->getID()),
        'deleteComment' => (string) $this->action('delete_comment', $locale->getID()),
        'loadAllPlaces' => (string) $this->action('load_all_places', $locale->getID()),
        'processTranslation' => (string) $this->action('process_translation', $locale->getID()),
        'loadTranslation' => (string) $this->action('load_translation', $locale->getID()),
    ],
    'tokens' => [
        'saveComment' => $token->generate('comtra-save-comment' . $locale->getID()),
        'deleteComment' => $token->generate('comtra-delete-comment' . $locale->getID()),
        'loadAllPlaces' => $token->generate('comtra-load-all-places' . $locale->getID()),
        'processTranslation' => $token->generate('comtra-process-translation' . $locale->getID()),
        'loadTranslation' => $token->generate('comtra-load-translation' . $locale->getID()),
    ],
    'i18n' => [
        'glossaryTypes' => GlossaryEntryType::getTypesInfo(),
        'no_translation_available' => t('no translation available'),
        'Are_you_sure' => t('Are you sure?'),
        'by' => tc('Prefix of an author name', 'by'),
        'Reply' => t('Reply'),
        'Edit' => t('Edit'),
        'Approve' => t('Approve'),
        'Deny' => t('Deny'),
        'Use_this' => tc('Translation', 'Use this'),
        'Comments' => t('Comments'),
        'References' => t('References'),
        'Unused_string' => t('This string is not used in any package.'),
        'Waiting_approval' => t('Waiting approval'),
        'pluralRuleNames' => [
            'zero' => tc('PluralCase', 'Zero'),
            'one' => tc('PluralCase', 'One'),
            'two' => tc('PluralCase', 'Two'),
            'few' => tc('PluralCase', 'Few'),
            'many' => tc('PluralCase', 'Many'),
            'other' => tc('PluralCase', 'Other'),
        ],
    ],
];
if ($canEditGlossary) {
    $params['actions'] += [
        'saveGlossaryTerm' => (string) $this->action('save_glossary_term', $locale->getID()),
        'deleteGlossaryTerm' => (string) $this->action('delete_glossary_term', $locale->getID()),
    ];
    $params['tokens'] += [
        'saveGlossaryTerm' => $token->generate('comtra-save-glossary-term' . $locale->getID()),
         'deleteGlossaryTerm' => $token->generate('comtra-delete-glossary-term' . $locale->getID()),
    ];
}
echo json_encode($params);
?>);

});
</script>

<?php View::element('footer_required', ['disableTrackingCode' => true, 'display_account_menu' => false]); ?>
</body></html>
