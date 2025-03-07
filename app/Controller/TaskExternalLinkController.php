<?php

namespace Kanboard\Controller;

use Kanboard\Core\Controller\PageNotFoundException;
use Kanboard\Core\ExternalLink\ExternalLinkProviderNotFound;

/**
 * Task External Link Controller
 *
 * @package  Kanboard\Controller
 * @author   Frederic Guillot
 */
class TaskExternalLinkController extends BaseController
{
    /**
     * First creation form
     *
     * @access public
     * @param array $values
     * @param array $errors
     * @throws PageNotFoundException
     * @throws \Kanboard\Core\Controller\AccessForbiddenException
     */
    public function find(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();

        $this->response->html($this->template->render('task_external_link/find', array(
            'values' => $values,
            'errors' => $errors,
            'task' => $task,
            'types' => $this->externalLinkManager->getTypes(),
        )));
    }

    /**
     * Second creation form
     *
     * @access public
     */
    public function create()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();

        try {
            $urls = explode("\r\n", isset($values['text']) ? $values['text'] : '');
            $index = 0;

            $this->response->html($this->template->render('task_external_link/formOpen', array('task' => $task)));
            foreach($urls as $url){
                if(!empty($url)){
                    $values['text'] = $url;
                    $provider = $this->externalLinkManager->setUserInput($values)->find();
                    $link = $provider->getLink();
                    $this->response->html($this->template->render('task_external_link/form', array(
                        'values' => array(
                            'title-'.$index => $link->getTitle(),
                            'url-'.$index => $url,
                            'link_type-'.$index => $provider->getType(),
                            'index' => $index,
                        ),
                        'dependencies' => $provider->getDependencies(),
                        'errors' => array(),
                        'task' => $task,
                    )));
                }
                $index = $index + 1;
            }
            $this->response->html($this->template->render('task_external_link/formClose'));

        } catch (ExternalLinkProviderNotFound $e) {
            $errors = array('text' => array(t('Unable to fetch link information.')));
            $this->find($values, $errors);
        }
    }

    /**
     * Save link
     *
     * @access public
     */
    public function save()
    {
        $task = $this->getTask();
        $values = $this->request->getValues();

        $countd = count($values)/4;
        $index = 0;
        while($index < $countd){
            $linkValues = array();
            $linkValues['task_id'] = $task['id'];
            $linkValues['url'] = $values['url-'.$index];
            $linkValues['title'] = $values['title-'.$index];
            $linkValues['link_type'] = $values['link_type-'.$index];
            $linkValues['dependency'] = $values['dependency-'.$index];
            list($valid, $errors) = $this->externalLinkValidator->validateCreation($linkValues);
            if ($valid) {
                if ($this->taskExternalLinkModel->create($linkValues) !== false) {
                    $this->flash->success(t('Link added successfully.'));
                } else {
                    $this->flash->success(t('Unable to create your link.'));
                }

            } else {
                $provider = $this->externalLinkManager->getProvider($linkValues['link_type']);
                $this->response->html($this->template->render('task_external_link/create', array(
                    'values' => $linkValues,
                    'errors' => $errors,
                    'dependencies' => $provider->getDependencies(),
                    'task' => $task,
                    'index' => $index,
                )));
            }
            $index = $index + 1;
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])), true);
    }

    /**
     * Edit form
     *
     * @access public
     * @param  array $values
     * @param  array $errors
     * @throws ExternalLinkProviderNotFound
     * @throws PageNotFoundException
     * @throws \Kanboard\Core\Controller\AccessForbiddenException
     */
    public function edit(array $values = array(), array $errors = array())
    {
        $task = $this->getTask();
        $link = $this->getExternalTaskLink($task);
        $provider = $this->externalLinkManager->getProvider($link['link_type']);

        $this->response->html($this->template->render('task_external_link/edit', array(
            'values'       => empty($values) ? $link : $values,
            'errors'       => $errors,
            'task'         => $task,
            'link'         => $link,
            'dependencies' => $provider->getDependencies(),
        )));
    }

    /**
     * Update link
     *
     * @access public
     */
    public function update()
    {
        $task = $this->getTask();
        $link = $this->getExternalTaskLink($task);

        $values = $this->request->getValues();
        $values['id'] = $link['id'];
        $values['task_id'] = $link['task_id'];

        list($valid, $errors) = $this->externalLinkValidator->validateModification($values);

        if ($valid && $this->taskExternalLinkModel->update($values)) {
            $this->flash->success(t('Link updated successfully.'));
            return $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])), true);
        }

        return $this->edit($values, $errors);
    }

    /**
     * Confirmation dialog before removing a link
     *
     * @access public
     */
    public function confirm()
    {
        $task = $this->getTask();
        $link = $this->getExternalTaskLink($task);

        $this->response->html($this->template->render('task_external_link/remove', array(
            'link' => $link,
            'task' => $task,
        )));
    }

    /**
     * Remove a link
     *
     * @access public
     */
    public function remove()
    {
        $this->checkCSRFParam();
        $task = $this->getTask();
        $link = $this->getExternalTaskLink($task);

        if ($this->taskExternalLinkModel->remove($link['id'])) {
            $this->flash->success(t('Link removed successfully.'));
        } else {
            $this->flash->failure(t('Unable to remove this link.'));
        }

        $this->response->redirect($this->helper->url->to('TaskViewController', 'show', array('task_id' => $task['id'])));
    }
}
