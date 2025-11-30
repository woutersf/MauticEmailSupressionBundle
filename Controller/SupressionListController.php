<?php

namespace MauticPlugin\MauticEmailSupressionBundle\Controller;

use Mautic\CoreBundle\Controller\AbstractStandardFormController;
use MauticPlugin\MauticEmailSupressionBundle\Entity\SuprList;
use MauticPlugin\MauticEmailSupressionBundle\Entity\SuprListDate;
use MauticPlugin\MauticEmailSupressionBundle\Model\SupressionListModel;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class SupressionListController extends AbstractStandardFormController
{
    protected function getPermissions(): array
    {
        return $this->security->isGranted(
            [
                'supressionlist:supressionlists:view',
                'supressionlist:supressionlists:create',
                'supressionlist:supressionlists:edit',
                'supressionlist:supressionlists:delete',
            ],
            'RETURN_ARRAY'
        );
    }

    /**
     * List all suppression lists
     */
    public function indexAction(Request $request, $page = 1)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        // Ensure page is at least 1
        $page = max(1, (int) $page);

        // Set limits
        $limit = $this->coreParametersHelper->get('default_pagelimit');

        // Set order
        $orderBy    = $request->get('orderby', 's.name');
        $orderByDir = $request->get('orderbydir', 'ASC');

        $search = $request->get('search', '');

        $filter = ['string' => $search];

        $tmpl = $request->get('tmpl', 'index');

        $result = $model->getEntities([
            'start'      => ($page - 1) * $limit,
            'limit'      => $limit,
            'filter'     => $filter,
            'orderBy'    => $orderBy,
            'orderByDir' => $orderByDir,
        ]);

        // Handle Paginator object
        if ($result instanceof \Doctrine\ORM\Tools\Pagination\Paginator) {
            $items = iterator_to_array($result->getIterator());
            $total = count($result);
        } else {
            $items = is_array($result) ? ($result['results'] ?? $result) : $result;
            $total = is_array($result) ? ($result['count'] ?? count($items)) : count($items);
        }

        return $this->delegateView([
            'viewParameters' => [
                'items'       => $items,
                'page'        => $page,
                'limit'       => $limit,
                'tmpl'        => $tmpl,
                'searchValue' => $search,
                'totalItems'  => $total,
                'permissions' => $this->getPermissions(),
            ],
            'contentTemplate' => '@MauticEmailSupression/SupressionList/list.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'supressionlist',
                'route'         => $this->generateUrl('mautic_supressionlist_index', ['page' => $page]),
            ],
        ]);
    }

    /**
     * Generate new form and processes post data
     */
    public function executeAction(Request $request, $objectAction, $objectId = 0, $objectSubId = 0, $objectModel = '')
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = null;
        if ('new' !== $objectAction && !empty($objectId)) {
            $entity = $model->getEntity($objectId);

            if (null === $entity) {
                return $this->notFound();
            }
        }

        if ('new' === $objectAction || 'edit' === $objectAction) {
            return $this->newAction($request, $entity);
        } elseif ('delete' === $objectAction) {
            return $this->deleteAction($request, $objectId);
        } elseif ('view' === $objectAction) {
            return $this->viewAction($request, $objectId);
        }

        return $this->notFound();
    }

    /**
     * Create or edit a suppression list
     */
    protected function newAction(Request $request, $entity = null)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        if (null === $entity) {
            $entity = new SuprList();
        }

        $isNew = null === $entity->getId();

        if (!$this->security->isGranted('supressionlist:supressionlists:' . ($isNew ? 'create' : 'edit'))) {
            return $this->accessDenied();
        }

        $action = $this->generateUrl('mautic_supressionlist_action', [
            'objectAction' => $isNew ? 'new' : 'edit',
            'objectId'     => $entity->getId(),
        ]);

        // Get currently selected segments and campaigns
        $selectedSegments = [];
        $selectedCampaigns = [];

        if (!$isNew) {
            $linkedData = $model->getLinkedSegmentsAndCampaigns($entity->getId());
            foreach ($linkedData['segments'] as $segment) {
                $selectedSegments[] = $segment->getId();
            }
            foreach ($linkedData['campaigns'] as $campaign) {
                $selectedCampaigns[] = $campaign->getId();
            }
        }

        $form = $model->createForm($entity, $this->formFactory, $action, [
            'selected_segments' => $selectedSegments,
            'selected_campaigns' => $selectedCampaigns,
        ]);

        if ('POST' === $request->getMethod()) {
            $valid = false;
            if (!$cancelled = $this->isFormCancelled($form)) {
                if ($valid = $this->isFormValid($form)) {
                    $model->saveEntity($entity);

                    // Get submitted segments and campaigns
                    $submittedSegments = $form->get('segments')->getData() ?? [];
                    $submittedCampaigns = $form->get('campaigns')->getData() ?? [];

                    // Save linked segments and campaigns
                    $model->saveLinkedSegmentsAndCampaigns($entity->getId(), $submittedSegments, $submittedCampaigns);

                    $this->addFlashMessage(
                        'mautic.core.notice.' . ($isNew ? 'created' : 'updated'),
                        [
                            '%name%' => $entity->getName(),
                        ]
                    );

                    if ($this->getFormButton($form, ['buttons', 'save'])->isClicked()) {
                        return $this->redirect($this->generateUrl('mautic_supressionlist_action', [
                            'objectAction' => 'view',
                            'objectId'     => $entity->getId(),
                        ]));
                    }
                }
            } else {
                // If editing and cancelled, redirect to detail page
                if (!$isNew) {
                    return $this->redirect($this->generateUrl('mautic_supressionlist_action', [
                        'objectAction' => 'view',
                        'objectId'     => $entity->getId(),
                    ]));
                }
                // If creating and cancelled, redirect to index
                return $this->redirect($this->generateUrl('mautic_supressionlist_index'));
            }
        }

        return $this->delegateView([
            'viewParameters' => [
                'form'        => $form->createView(),
                'entity'      => $entity,
                'permissions' => $this->getPermissions(),
            ],
            'contentTemplate' => '@MauticEmailSupression/SupressionList/form.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'supressionlist',
                'route'         => $action,
            ],
        ]);
    }

    /**
     * View a suppression list
     */
    protected function viewAction(Request $request, $objectId)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = $model->getEntity($objectId);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->security->isGranted('supressionlist:supressionlists:view')) {
            return $this->accessDenied();
        }

        // Get all dates for this suppression list
        $dates = $model->getDatesBySuprListId($objectId);

        // Format dates as ranges
        $formattedDates = $this->formatDatesAsRanges($dates);

        // Get linked segments and campaigns
        $linkedData = $model->getLinkedSegmentsAndCampaigns($objectId);

        return $this->delegateView([
            'viewParameters' => [
                'entity'         => $entity,
                'dates'          => $dates,
                'formattedDates' => $formattedDates,
                'segments'       => $linkedData['segments'],
                'campaigns'      => $linkedData['campaigns'],
                'permissions'    => $this->getPermissions(),
            ],
            'contentTemplate' => '@MauticEmailSupression/SupressionList/details.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'supressionlist',
                'route'         => $this->generateUrl('mautic_supressionlist_action', [
                    'objectAction' => 'view',
                    'objectId'     => $objectId,
                ]),
            ],
        ]);
    }

    /**
     * Format dates as ranges (consecutive dates shown as "dd-mm-yyyy - dd-mm-yyyy")
     */
    private function formatDatesAsRanges(array $dates): array
    {
        if (empty($dates)) {
            return [];
        }

        // Sort dates
        usort($dates, function ($a, $b) {
            return $a->getDate() <=> $b->getDate();
        });

        $ranges = [];
        $rangeStart = null;
        $rangeEnd = null;

        foreach ($dates as $dateEntity) {
            $currentDate = $dateEntity->getDate();

            if (null === $rangeStart) {
                $rangeStart = $currentDate;
                $rangeEnd = $currentDate;
                continue;
            }

            // Check if current date is consecutive to range end
            $nextDay = clone $rangeEnd;
            $nextDay->modify('+1 day');

            if ($currentDate->format('Y-m-d') === $nextDay->format('Y-m-d')) {
                // Extend the range
                $rangeEnd = $currentDate;
            } else {
                // Save the current range and start a new one
                $ranges[] = $this->formatDateRange($rangeStart, $rangeEnd);
                $rangeStart = $currentDate;
                $rangeEnd = $currentDate;
            }
        }

        // Add the last range
        if (null !== $rangeStart) {
            $ranges[] = $this->formatDateRange($rangeStart, $rangeEnd);
        }

        return $ranges;
    }

    /**
     * Format a date range as string
     */
    private function formatDateRange(\DateTime $start, \DateTime $end): string
    {
        if ($start->format('Y-m-d') === $end->format('Y-m-d')) {
            return $start->format('d - F - Y');
        }

        return $start->format('d - F - Y') . ' — ' . $end->format('d - F - Y');
    }

    /**
     * Delete a suppression list
     */
    protected function deleteAction(Request $request, $objectId)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = $model->getEntity($objectId);

        if (null === $entity) {
            return $this->notFound();
        }

        if (!$this->security->isGranted('supressionlist:supressionlists:delete')) {
            return $this->accessDenied();
        }

        $model->deleteEntity($entity);

        $this->addFlashMessage(
            'mautic.core.notice.deleted',
            [
                '%name%' => $entity->getName(),
            ]
        );

        return $this->redirect($this->generateUrl('mautic_supressionlist_index'));
    }

    /**
     * Display calendar view
     */
    public function calendarAction(Request $request, $id, $year = null)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = $model->getEntity($id);

        if (null === $entity) {
            return $this->notFound();
        }

        // If no year specified, use current year
        if (null === $year) {
            return $this->redirect($this->generateUrl('mautic_supressionlist_calendar', [
                'id'   => $id,
                'year' => date('Y'),
            ]));
        }

        // Get all dates for this suppression list
        $dates = $model->getDatesBySuprListId($id);

        // Convert dates to array of date strings for easier JS handling
        $markedDates = [];
        foreach ($dates as $dateEntity) {
            $markedDates[] = $dateEntity->getDate()->format('Y-m-d');
        }

        return $this->delegateView([
            'viewParameters' => [
                'entity'      => $entity,
                'year'        => (int) $year,
                'markedDates' => $markedDates,
                'permissions' => $this->getPermissions(),
            ],
            'contentTemplate' => '@MauticEmailSupression/SupressionList/calendar.html.twig',
            'passthroughVars' => [
                'mauticContent' => 'supressionlist',
                'route'         => $this->generateUrl('mautic_supressionlist_calendar', [
                    'id'   => $id,
                    'year' => $year,
                ]),
            ],
        ]);
    }

    /**
     * List all dates (JSON response)
     */
    public function datesAction(Request $request, $id)
    {
        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = $model->getEntity($id);

        if (null === $entity) {
            return new JsonResponse(['error' => 'Not found'], 404);
        }

        // Get all dates for this suppression list
        $dates = $model->getDatesBySuprListId($id);

        $dateList = [];
        foreach ($dates as $dateEntity) {
            $dateList[] = $dateEntity->getDate()->format('Y-m-d');
        }

        return new JsonResponse([
            'success' => true,
            'dates'   => $dateList,
        ]);
    }

    /**
     * Toggle a date (AJAX endpoint)
     */
    public function toggleDateAction(Request $request, $id, $date, $action)
    {
        // Disable CSRF check for this AJAX endpoint
        $request->attributes->set('_disable_csrf_check', true);

        if (!$request->isXmlHttpRequest() || 'POST' !== $request->getMethod()) {
            return new JsonResponse(['error' => 'Invalid request'], 400);
        }

        /** @var SupressionListModel $model */
        $model = $this->getModel('supressionlist.supressionlist');

        $entity = $model->getEntity($id);

        if (null === $entity) {
            return new JsonResponse(['error' => 'Suppression list not found'], 404);
        }

        try {
            $dateObj = new \DateTime($date);
            // Normalize to start of day for consistency
            $dateObj->setTime(0, 0, 0);
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Invalid date format'], 400);
        }

        $em = $this->getDoctrine()->getManager();

        if ('TRUE' === strtoupper($action)) {
            // Add date
            $existingDate = $model->findDateBySuprListAndDate($id, $dateObj);

            if (null === $existingDate) {
                $dateEntity = new SuprListDate();
                $dateEntity->setSuprList($entity);
                $dateEntity->setDate($dateObj);

                $em->persist($dateEntity);
                $em->flush();

                return new JsonResponse([
                    'success' => true,
                    'action'  => 'added',
                    'date'    => $date,
                ]);
            }

            return new JsonResponse([
                'success' => true,
                'action'  => 'already_exists',
                'date'    => $date,
            ]);
        } else {
            // Remove date
            $deletedCount = $model->deleteDateBySuprListAndDate($id, $dateObj);

            return new JsonResponse([
                'success' => true,
                'action'  => 'removed',
                'date'    => $date,
                'deleted_count' => $deletedCount,
                'debug' => [
                    'supr_list_id' => $id,
                    'date' => $dateObj->format('Y-m-d'),
                    'action_param' => $action,
                ],
            ]);
        }
    }

    protected function getModelName(): string
    {
        return 'supressionlist.supressionlist';
    }

    protected function getRouteBase(): string
    {
        return 'mautic_supressionlist';
    }

    protected function getSessionBase($objectId = null): string
    {
        return 'supressionlist' . (null !== $objectId ? '.' . $objectId : '');
    }

    protected function getTemplateBase(): string
    {
        return '@MauticEmailSupressionBundle/SupressionList';
    }

    protected function getTranslationBase(): string
    {
        return 'mautic.supressionlist';
    }
}
