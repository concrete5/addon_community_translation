<?php
namespace CommunityTranslation\Service;

use CommunityTranslation\Locale\Locale;
use CommunityTranslation\Package\Package;
use CommunityTranslation\Service\User as UserService;
use CommunityTranslation\Translatable\Comment\Comment;
use CommunityTranslation\Translatable\Translatable;
use Concrete\Core\Application\Application;

class Editor implements \Concrete\Core\Application\ApplicationAwareInterface
{
    /**
     * Maximum number of translation suggestions.
     *
     * @var int
     */
    const MAX_SUGGESTIONS = 15;

    /**
     * Maximum number of glossary terms to show in the editor.
     *
     * @var int
     */
    const MAX_GLOSSARY_ENTRIES = 15;

    /**
     * The application object.
     *
     * @var Application
     */
    protected $app;

    /**
     * Set the application object.
     *
     * @param Application $application
     */
    public function setApplication(Application $app)
    {
        $this->app = $app;
    }

    /**
     * Returns the initial translations to be reviewed for the online editor, for a specific locale.
     *
     * @param Locale $locale
     *
     * @return array
     */
    public function getUnreviewedInitialTranslations(Locale $locale)
    {
        $rs = $this->app->make('community_translation/translation/exporter')->getUnreviewedSelectQuery($locale);

        return $this->buildInitialTranslations($locale, $rs);
    }

    /**
     * Returns the initial translations for the online editor, for a specific package.
     *
     * @param Package $package
     * @param Locale $locale
     *
     * @return array
     */
    public function getInitialTranslations(Package $package, Locale $locale)
    {
        $rs = $this->app->make('community_translation/translation/exporter')->getPackageSelectQuery($package, $locale, false);

        return $this->buildInitialTranslations($locale, $rs);
    }

    /**
     * Builds the initial translations array.
     *
     * @param \Concrete\Core\Database\Driver\PDOStatement $rs
     *
     * @return array
     */
    protected function buildInitialTranslations(Locale $locale, \Concrete\Core\Database\Driver\PDOStatement $rs)
    {
        $result = [];
        $numPlurals = $locale->getPluralCount();
        while (($row = $rs->fetch()) !== false) {
            $item = [
                'id' => (int) $row['tID'],
                'original' => $row['tText'],
            ];
            if ($row['tContext'] !== '') {
                $item['context'] = $row['tContext'];
            }
            $isPlural = $row['tPlural'] !== '';
            if ($isPlural) {
                $item['originalPlural'] = $row['tPlural'];
            }
            if ($row['tText0'] !== null) {
                $translations = [];
                switch ($isPlural ? $numPlurals : 1) {
                    case 6:
                        $translations[] = $row['tText5'];
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case 5:
                        $translations[] = $row['tText4'];
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case 4:
                        $translations[] = $row['tText3'];
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case 3:
                        $translations[] = $row['tText2'];
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case 2:
                        $translations[] = $row['tText1'];
                        /* @noinspection PhpMissingBreakStatementInspection */
                    case 1:
                        $translations[] = $row['tText0'];
                        break;
                }
                $item['translations'] = array_reverse($translations);
            }
            $result[] = $item;
        }
        $rs->closeCursor();

        return $result;
    }

    /**
     * Returns the data to be used in the editor when editing a string.
     *
     * @param Locale $locale the current editor locale
     * @param Translatable $translatable the source string that's being translated
     * @param Package $package the package where this string is used
     * @param bool $initial set to true when a string is first loaded, false after it has been saved
     *
     * @return array
     */
    public function getTranslatableData(Locale $locale, Translatable $translatable, Package $package = null, $initial = false)
    {
        $result = [
            'id' => $translatable->getID(),
            'translations' => $this->getTranslations($locale, $translatable),
        ];
        if ($initial) {
            $place = ($package === null) ? null : $this->app->make('community_translation/translatable/place')->findOneBy(['tpPackage' => $package, 'tpTranslatable' => $translatable]);
            if ($place !== null) {
                $extractedComments = $place->getComments();
                $references = $this->expandReferences($place->getLocations(), $package);
            } else {
                $extractedComments = [];
                $references = [];
            }
            $result['extractedComments'] = $extractedComments;
            $result['references'] = $references;
            $result['extractedComments'] = ($place === null) ? [] : $place->getComments();
            $result['comments'] = $this->getComments($locale, $translatable);
            $result['suggestions'] = $this->getSuggestions($locale, $translatable);
            $result['glossary'] = $this->getGlossaryTerms($locale, $translatable);
        }

        return $result;
    }

    /**
     * Search all the translations associated to a translatable string.
     *
     * @param Locale $locale
     * @param Translatable $translatable
     *
     * @return array
     */
    public function getTranslations(Locale $locale, Translatable $translatable)
    {
        $numPlurals = $locale->getPluralCount();

        $result = [
            'current' => null,
            'others' => [],
        ];
        $translations = $this->app->make('community_translation/translation')->findBy(['tTranslatable' => $translatable, 'tLocale' => $locale], ['tCreatedOn' => 'DESC']);
        $dh = $this->app->make('helper/date');
        $uh = $this->app->make(UserService::class);
        foreach ($translations as $translation) {
            /* @var \CommunityTranslation\Translation\Translation $translation */
            $texts = [];
            switch (($translatable->getPlural() === '') ? 1 : $numPlurals) {
                case 6:
                    $texts[] = $translation->getText5();
                    /* @noinspection PhpMissingBreakStatementInspection */
                case 5:
                    $texts[] = $translation->getText4();
                    /* @noinspection PhpMissingBreakStatementInspection */
                case 4:
                    $texts[] = $translation->getText3();
                    /* @noinspection PhpMissingBreakStatementInspection */
                case 3:
                    $texts[] = $translation->getText2();
                    /* @noinspection PhpMissingBreakStatementInspection */
                case 2:
                    $texts[] = $translation->getText1();
                    /* @noinspection PhpMissingBreakStatementInspection */
                case 1:
                default:
                    $texts[] = $translation->getText0();
                    break;
            }
            $item = [
                'id' => $translation->getID(),
                'createdOn' => $dh->formatPrettyDateTime($translation->getCreatedOn(), false, true),
                'createdBy' => $uh->format($translation->getCreatedBy()),
                'reviewed' => $translation->isReviewed(),
                'translations' => array_reverse($texts),
            ];
            if ($translation->isCurrent()) {
                $item['currentSince'] = $dh->formatPrettyDateTime($translation->isCurrentSince(), false, true);
                $result['current'] = $item;
            } else {
                $item['needReview'] = $translation->needReview();
                $result['others'][] = $item;
            }
        }

        return $result;
    }

    /**
     * Get the comments associated to a translatable strings.
     *
     * @param Locale $locale
     * @param Translatable $translatable
     */
    public function getComments(Locale $locale, Translatable $translatable, Comment $parentComment = null)
    {
        $repo = $this->app->make('community_translation/translatable/comment');
        if ($parentComment === null) {
            $qb = $repo->createQueryBuilder('c');
            $qb
                ->where('c.tcTranslatable = :translatable')
                ->andWhere('c.tcParentComment is null')
                ->andWhere($qb->expr()->orX(
                    'c.tcLocale = :locale',
                    'c.tcLocale is null'
                ))
                ->orderBy('c.tcPostedOn', 'ASC')
                ->setParameter('translatable', $translatable)
                ->setParameter('locale', $locale)
                ;
            $comments = $qb->getQuery()->getResult();
        } else {
            $comments = $repo->findBy(
                ['tcParentComment' => $parentComment],
                ['tcPostedOn' => 'ASC']
            );
        }
        $result = [];
        $uh = $this->app->make(UserService::class);
        $dh = $this->app->make('helper/date');
        $me = new \User();
        $myID = $me->isRegistered() ? (int) $me->getUserID() : null;
        foreach ($comments as $comment) {
            $result[] = [
                'id' => $comment->getID(),
                'date' => $dh->formatPrettyDateTime($comment->getPostedOn(), true, true),
                'mine' => $myID && $myID === $comment->getPostedBy(),
                'by' => $uh->format($comment->getPostedBy()),
                'text' => $comment->getText(),
                'comments' => $this->getComments($locale, $translatable, $comment),
                'isGlobal' => $comment->getLocale() === null,
            ];
        }

        return $result;
    }

    /**
     * Search for similar translations.
     *
     * @param Locale $locale
     * @param Translatable $translatable
     *
     * @return array
     */
    public function getSuggestions(Locale $locale, Translatable $translatable)
    {
        $result = [];
        $connection = $this->app->make('community_translation/em')->getConnection();
        $rs = $connection->executeQuery(
            '
                select distinct
                    Translatables.tText,
                    Translations.tText0,
                    match(Translatables.tText) against (:search in natural language mode) as relevance
                from
                    Translations
                    inner join Translatables on Translations.tTranslatable = Translatables.tID and 1 = Translations.tCurrent and :locale = Translations.tLocale
                where
                    Translatables.tID <> :currentTranslatableID
                    and length(Translatables.tText) between :minLength and :maxLength
                having
                    relevance > 0
                order by
                    relevance desc,
                    tText asc
                limit
                    0, ' . ((int) self::MAX_SUGGESTIONS) . '
            ',
            [
                'search' => $translatable->getText(),
                'locale' => $locale->getID(),
                'currentTranslatableID' => $translatable->getID(),
                'minLength' => (int) floor(strlen($translatable->getText()) * 0.75),
                'maxLength' => (int) ceil(strlen($translatable->getText()) * 1.33),
            ]
        );
        while ($row = $rs->fetch()) {
            $result[] = [
                'source' => $row['tText'],
                'translation' => $row['tText0'],
            ];
        }
        $rs->closeCursor();

        return $result;
    }

    /**
     * Search the glossary entries to show when translating a string in a specific locale.
     *
     * @param Locale $locale the current editor locale
     * @param Translatable $translatable the source string that's being translated
     *
     * @return array
     */
    public function getGlossaryTerms(Locale $locale, Translatable $translatable)
    {
        $result = [];
        $connection = $this->app->make('community_translation/em')->getConnection();
        $rs = $connection->executeQuery(
            '
                select
                    geID,
                    geTerm,
                    geType,
                    geComments,
                    gleTranslation,
                    gleComments,
                    match(geTerm) against (:search in natural language mode) as relevance
                from
                    GlossaryEntries
                    left join GlossaryLocalizedEntries on GlossaryEntries.geID = GlossaryLocalizedEntries.gleEntry and :locale = GlossaryLocalizedEntries.gleLocale
                having
                    relevance > 0
                order by
                    relevance desc,
                    geTerm asc
                limit
                    0, ' . ((int) self::MAX_GLOSSARY_ENTRIES) . '
            ',
            [
                'search' => $translatable->getText(),
                'locale' => $locale->getID(),
            ]
        );
        while ($row = $rs->fetch()) {
            $result[] = [
                'id' => (int) $row['geID'],
                'term' => $row['geTerm'],
                'type' => $row['geType'],
                'termComments' => $row['geComments'],
                'translation' => ($row['gleTranslation'] === null) ? '' : $row['gleTranslation'],
                'translationComments' => ($row['gleComments'] === null) ? '' : $row['gleComments'],
            ];
        }
        $rs->closeCursor();

        return $result;
    }

    /**
     * Expand translatable string references by adding a link to the online repository where they are defined.
     *
     * @param string[] $references
     * @param Package $package
     *
     * @return string[]
     */
    public function expandReferences(array $references, Package $package)
    {
        if (empty($references)) {
            return $references;
        }
        $repositories = $this->app->make('community_translation/git')->findBy(['grPackage' => $package->getHandle()]);
        $applicableRepository = null;
        $gitSubDir = '';
        if (strpos($package->getVersion(), Package::DEV_PREFIX) === 0) {
            foreach ($repositories as $repository) {
                foreach ($repository->getDevBranches() as $devBranch => $packageVersion) {
                    if ($package->getVersion() === $packageVersion) {
                        $gitSubDir = 'blob/' . $devBranch . '/';
                        $applicableRepository = $repository;
                        break;
                    }
                }
                if ($applicableRepository !== null) {
                    break;
                }
            }
        } else {
            foreach ($repositories as $repository) {
                $vx = $repository->getTagsFilterExpanded();
                if ($vx !== null && version_compare($package->getVersion(), $vx['version'], $vx['operator'])) {
                    $gitSubDir = 'blob/' . $package->getVersion() . '/';
                    $applicableRepository = $repository;
                    break;
                }
            }
        }
        if ($applicableRepository === null) {
            return $references;
        }
        if (!preg_match('/^(https?:\/\/github.com\/[^?]+)\.git($|\?)/i', $applicableRepository->getURL(), $matches)) {
            return $references;
        }
        $baseURL = $matches[1] . '/' . $gitSubDir;
        if ($applicableRepository->getDirectoryToParse() !== '') {
            $baseURL .= $applicableRepository->getDirectoryToParse() . '/';
        }
        foreach ($references as $index => $reference) {
            if (!preg_match('/^\w*:\/\//', $reference)) {
                $url = $baseURL . ltrim($reference, '/');
                if (preg_match('/^(.+):(\d+)$/', $url, $m)) {
                    $url = $m[1] . '#L' . $m[2];
                }
                $references[$index] = [$url, $reference];
            }
        }

        return $references;
    }
}
