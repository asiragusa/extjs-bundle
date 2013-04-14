<?php

namespace {{ namespace }}\Controller;

{% block use_statements %}
use Doctrine\ORM\EntityManager;
use FOS\RestBundle\Controller\FOSRestController;
use FOS\RestBundle\Request\ParamFetcher;
use FOS\RestBundle\Controller\Annotations\QueryParam;
use FOS\RestBundle\View\View;
use Symfony\Component\Form\Form;
use {{ entity_class }};
use {{ entity_type_class }};
{% if 'annotation' == format.routing -%}
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
{% endif %}
{% endblock use_statements %}

{% block class_definition %}
class {{ controller }}Controller extends FOSRestController
{% endblock class_definition %}
{
{% block class_body %}
    /**
     * Get detail of a {{ entity_name }} record
     * @param              $id
     *
     * @QueryParam(name="id", requirements="\d+")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get{{ entity_name|capitalize }}Action($id) {
        /** @var $manager EntityManager */
        $manager = $this->get('doctrine.orm.default_entity_manager');
        $entity = $manager->getRepository('{{ bundle }}:{{ entity }}')->find($id);
        $view = View::create([$entity], 200);
        return $this->handleView($view);
    }

    /**
     * Get list of {{ entity_name }} record
     * @param ParamFetcher $param
     *
     * @QueryParam(name="page", requirements="\d+", default="1", description="Page of the list.")
     * @QueryParam(name="pageSize", requirements="\d+", default="10", description="Number of warehouse per page.")
     * @QueryParam(name="sort", description="Sort result by field")
     * @QueryParam(name="query", description="")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function get{{ entity_name|capitalize }}sAction(ParamFetcher $param) {
        /** @var $manager EntityManager */
        $manager = $this->get('doctrine.orm.default_entity_manager');
        $list = $manager->getRepository('{{ bundle }}:{{ entity }}')->findBy(
            $param->get('query'),
            $param->get('sort'),
            (int)$param->get('pageSize'),
            ($param->get('page')-1)*$param->get('pageSize')
        );
        $view = View::create($list, 200);
        return $this->handleView($view);
    }

    /**
     * Create a new {{ entity_name }} record
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function post{{ entity_name|capitalize }}Action() {
        $entity = new {{ entity }}();
        $entity->setCreatedBy($this->getUser());
        return $this->processForm($this->createForm(
            new {{ entity_type }}(),
            $entity
        ));
    }

    /**
     * Update an existing {{ entity_name }} record
     * @param $id
     *
     * @QueryParam(name="id", requirements="\d+")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function put{{ entity_name|capitalize }}Action($id) {
        /** @var $manager EntityManager */
        $manager = $this->get('doctrine.orm.default_entity_manager');
        $entity = $manager->getRepository('{{ bundle }}:{{ entity }}')->find($id);
        if ($entity === null) {
            return $this->handleView(View::create(null, 404));
        } else {
            return $this->processForm($this->createForm(
                new {{ entity_type }}(),
                $entity
            ));
        }
    }

    /**
     * Delete an existing {{ entity_name }} record
     * @param $id
     *
     * @QueryParam(name="id", requirements="\d+")
     *
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function delete{{ entity_name|capitalize }}Action($id) {
        /** @var $manager EntityManager */
        $manager = $this->get('doctrine.orm.default_entity_manager');
        $entity = $manager->getRepository('{{ bundle }}:{{ entity }}')->find($id);
        $manager->remove($entity);
        $manager->flush();
        return $this->handleView(View::create(null, 200));
    }

    /**
     * Process Form.
     */
    protected function processForm(Form $form) {
        $parameters = $this->getRequest()->request->all();
        unset($parameters['id']);
        $form->bind($parameters);
        if ($form->isValid()) {
            /** @var $model Warehouse */
            $model = $form->getData();
            $model->setModifiedBy($this->getUser());
            $manager = $this->get('doctrine.orm.default_entity_manager');
            $manager->persist($model);
            $manager->flush();
            return $this->handleView(View::create([$model], 200));
        }
        return $this->handleView(View::create(['errors'=>$form->getErrors()], 400));
    }

{% for action in actions %}
    {% if 'annotation' == format.routing -%}
    /**
     * @Route("{{ action.route }}")
    {% if 'default' == action.template -%}
     * @Template()
    {% else -%}
     * @Template("{{ action.template }}")
    {% endif -%}
     */
    {% endif -%}
    public function {{ action.name }}(
        {%- if action.placeholders|length > 0 -%}
            ${{- action.placeholders|join(', $') -}}
        {%- endif -%})
    {
    }
{% endfor -%}
{% endblock class_body %}
}
