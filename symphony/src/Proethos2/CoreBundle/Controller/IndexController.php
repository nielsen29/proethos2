<?php

// This file is part of the ProEthos Software. 
// 
// Copyright 2013, PAHO. All rights reserved. You can redistribute it and/or modify
// ProEthos under the terms of the ProEthos License as published by PAHO, which
// restricts commercial use of the Software. 
// 
// ProEthos is distributed in the hope that it will be useful, but WITHOUT ANY
// WARRANTY; without even the implied warranty of MERCHANTABILITY or FITNESS FOR A
// PARTICULAR PURPOSE. See the ProEthos License for more details. 
// 
// You should have received a copy of the ProEthos License along with the ProEthos
// Software. If not, see
// https://github.com/bireme/proethos2/blob/master/LICENSE.txt


namespace Proethos2\CoreBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;

use Proethos2\CoreBundle\Util\Util;


class IndexController extends Controller
{
    /**
     * @Route("/", name="home")
     * @Template()
     */
    public function indexAction()
    {
        $output = array();
        $request = $this->getRequest();
        $session = $request->getSession();
        $translator = $this->get('translator');
        $em = $this->getDoctrine()->getManager();

        $protocol_repository = $em->getRepository('Proethos2ModelBundle:Protocol');
        $protocol_revision_repository = $em->getRepository('Proethos2ModelBundle:ProtocolRevision');
        $meeting_repository = $em->getRepository('Proethos2ModelBundle:Meeting');

        $user = $this->get('security.token_storage')->getToken()->getUser();

        if ( count($user->getRolesSlug()) == 1 and 'administrator' == $user->getRolesSlug()[0] ) {
            return $this->redirectToRoute('crud_admin_configuration_list', array(), 301);
        }

        $revisions = array();
        foreach($protocol_revision_repository->findBy(array("member" => $user)) as $revision) {
            if (in_array($revision->getProtocol()->getStatus(), array('E', 'H'))) {
                $revisions[] = $revision->getProtocol();
            }
        }
        foreach($protocol_repository->findBy(array("status" => "I")) as $protocol) {
            $revisions[] = $protocol;
        }
        $output['revisions'] = $revisions;
        
        $submissions = array();
        foreach($protocol_repository->findBy(array("owner" => $user), array('id' => 'DESC')) as $submission) {
            $submissions[] = $submission;
        }
        $output['submissions'] = $submissions;

        $now = new \DateTime();
        $twoMonths = new \DateTime();
        $twoMonths = $twoMonths->add(new \DateInterval("P60D"));
        
        $qb = $meeting_repository->createQueryBuilder('m');
        $query = $qb->add('where', $qb->expr()->between(
                'm.date',
                ':from',
                ':to'
            )
        )
            ->setParameters(array('from' => $now, 'to' => $twoMonths))
            ->getQuery();

        $meetings = $query->getResult();

        $output['meetings'] = $meetings;
        
        return $output;
    }

    /**
     * @Route("/locale/{locale}", name="change_locale")
     * @Template()
     */
    public function changeLocaleAction($locale)
    {
        $request = $this->getRequest();

        $_locale = array('en', 'pt_BR', 'es_ES', 'fr_FR');

        if ( !in_array($locale, $_locale) ) {
            $locale = 'en';
        }

        // some logic to determine the $locale
        $request->getSession()->set('_locale', $locale);

        $referer = $request->headers->get('referer');
        return new RedirectResponse($referer);
    }

    public function getGoogleAnalyticsCodeAction()
    {   
        $util = new Util($this->container, $this->getDoctrine());
        $ga = $util->getConfiguration("google.analytics");

        if( $ga ) {
            return new Response($ga);
        }

        return new Response();

    }

    public function getLogoAction()
    {   
        $util = new Util($this->container, $this->getDoctrine());
        $committee_logourl = $util->getConfiguration("committee.logourl");
        $committee_logourl = explode("\n", $committee_logourl);

        if( $committee_logourl ) {
            return new Response($committee_logourl[0]);
        }

        return new Response();

    }

    public function getLogoFooterAction()
    {   
        $util = new Util($this->container, $this->getDoctrine());
        $committee_logourl = $util->getConfiguration("committee.logourl");
        $committee_logourl = explode("\n", $committee_logourl);

        if( $committee_logourl && count($committee_logourl) == 2 ) {
            return new Response($committee_logourl[1]);
        }

        return new Response();

    }
}
