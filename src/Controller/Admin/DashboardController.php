<?php
/*
 * Copyright (C) 2020  Jan Böhmer
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published
 * by the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 */

namespace App\Controller\Admin;

use App\Entity\BankAccount;
use App\Entity\Contracts\DBElementInterface;
use App\Entity\Contracts\UUIDDBElementInterface;
use App\Entity\Department;
use App\Entity\PaymentOrder;
use App\Entity\PostalVotingRegistration;
use App\Entity\User;
use App\Services\GitVersionInfo;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\Menu\CrudMenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\UserMenu;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Languages;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\User\UserInterface;

class DashboardController extends AbstractDashboardController
{
    private $app_version;
    private $gitVersionInfo;

    private const FILTER_DATETIME_FORMAT = 'Y-m-d\TH:i:s';

    public function __construct(string $app_version, GitVersionInfo $gitVersionInfo)
    {
        $this->app_version = $app_version;
        $this->gitVersionInfo = $gitVersionInfo;
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Urabstimmung');
    }

    /**
     * @Route("/admin", name="admin_dashboard", )
     */
    public function index(): Response
    {
        return $this->render('admin/dashboard.html.twig');
    }

    private function addFiltersToMenuItem(CrudMenuItem $menuItem, array $filters): CrudMenuItem
    {
        //Set referrer or we encounter errrors... (not needed in JB custom version))

        //$referrer = $this->crud_url_generator->build()->currentPageReferrer;
        //$menuItem->setQueryParameter('referrer', $referrer);

        foreach ($filters as $filter => $value) {
            if (is_array($value)) {
                foreach ($value as $subfilter => $subvalue) {
                    $menuItem->setQueryParameter('filters['.$filter.']['.$subfilter.']', $subvalue);
                }
            } else {
                $menuItem->setQueryParameter('filters['.$filter.']', $value);
            }
        }

        $menuItem->setQueryParameter('crudAction', 'index');

        return $menuItem;
    }

    public function configureMenuItems(): iterable
    {

        yield MenuItem::subMenu('registration.labelp', 'fas fa-vote-yea')
            ->setPermission('ROLE_REGISTRATION_VIEW')
            ->setSubItems([
                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.verification_required', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 1,
                        'unwarranted' => 0,
                        'verified' => 0,
                    ]
                ),
                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.printing_required', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 1,
                        'verified' => 1,
                        'unwarranted' => 0,
                        'printed' => 0,
                    ]
                ),
                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.count_required', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 1,
                        'verified' => 1,
                        'unwarranted' => 0,
                        'printed' => 1,
                    ]
                ),
                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.counted', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 1,
                        'verified' => 1,
                        'counted' => 1,
                    ]
                ),
                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.unconfirmed', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 0
                    ]
                ),

                $this->addFiltersToMenuItem(
                    MenuItem::linkToCrud('registration.menu.unwarranted', '', PostalVotingRegistration::class),
                    [
                        'confirmed' => 1,
                        'unwarranted' => 1,
                    ]
                ),


                MenuItem::linkToCrud('registration.menu.all', '', PostalVotingRegistration::class)
            ]);

        yield MenuItem::linkToCrud('user.labelp', 'fas fa-user', User::class)
            ->setPermission('ROLE_READ_USER');

        $version = $this->app_version.'-'.$this->gitVersionInfo->getGitCommitHash() ?? '';
        yield MenuItem::section('Version '.$version, 'fas fa-info');
        yield MenuItem::linktoRoute('dashboard.menu.audits', 'fas fa-binoculars', 'dh_auditor_list_audits')
            ->setPermission('ROLE_VIEW_AUDITS');
        yield MenuItem::linktoRoute('dashboard.menu.postal_voting_count', 'fas fa-flag', 'postal_voting_count')
            ->setPermission('ROLE_REGISTRATION_COUNT');
        yield MenuItem::linktoRoute('dashboard.menu.homepage', 'fas fa-home', 'homepage');
        yield MenuItem::linkToUrl('dashboard.menu.stura', 'fab fa-rebel', 'https://www.stura.uni-jena.de/');
        yield MenuItem::linkToUrl('dashboard.menu.github', 'fab fa-github', 'https://github.com/jbtronics/stura-urabstimmung');
    }

    public function configureUserMenu(UserInterface $user): UserMenu
    {
        /** @var User $user */

        return parent::configureUserMenu($user)
            ->setName((string) $user)
            ->displayUserName(true)
            ->addMenuItems([
                MenuItem::linktoRoute('user.settings.title', 'fas fa-user-cog', 'user_settings'),
                //It is important to use LinkToUrl here. LinkToCrud will put the route name into a param, but does not change the prefix
                MenuItem::linkToUrl(Languages::getName('de', 'de').' (DE)', '', '/de/admin'),
                MenuItem::linkToUrl(Languages::getName('en', 'en').' (EN)', '', '/en/admin'),
            ]);
    }

    public function configureActions(): Actions
    {
        $actions = parent::configureActions();

        $showLog = Action::new('showLog', 'action.show_logs', 'fas fa-binoculars')
            ->displayIf(function ($entity) {
                return $this->isGranted('ROLE_VIEW_AUDITS');
            })
            ->setCssClass('ml-2 text-dark')
            ->linkToRoute('dh_auditor_show_entity_history', function ($entity) {
                if ($entity instanceof DBElementInterface) {
                    return [
                        'entity' => str_replace('\\', '-', get_class($entity)),
                        'id' => $entity->getId(),
                    ];
                } elseif ($entity instanceof UUIDDBElementInterface) {
                    return [
                        'entity' => str_replace('\\', '-', get_class($entity)),
                        'id' => $entity->getId()->toRfc4122(),
                    ];
                }

                throw new \InvalidArgumentException('$entity must have an ID property!');
            });

        return $actions
            ->add(Crud::PAGE_DETAIL, $showLog)
            ->add(Crud::PAGE_EDIT, $showLog);
    }

    public function configureCrud(): Crud
    {
        return parent::configureCrud()
            ->setPaginatorPageSize(50);
    }

    public function configureAssets(): Assets
    {
        return Assets::new()
            //->addJsFile('configurable-date-input-polyfill.dist.js')
            ->addCssFile('admin_styles.css');
    }
}
