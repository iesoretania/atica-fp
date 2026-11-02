<?php

declare(strict_types=1);

namespace App\Form\Type;

use App\Form\DataTransformer\RemoteEntityTransformer;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\Routing\RouterInterface;

/**
 * Campo de selección de una entidad (o varias) con búsqueda remota: la lista se carga desde una ruta mientras se escribe
 * (controlador Stimulus «entity-select», Tom Select). Sustituye a Select2EntityType de tetranz/select2entity-bundle.
 *
 * Opciones: class, text_property (propiedad que se muestra; por defecto, __toString), remote_route (+ remote_params) o
 * remote_path, minimum_input_length, multiple, placeholder, allow_clear, scroll, page_limit, render_html,
 * query_parameters, class_type y req_params (campos del formulario cuyo valor se envía en la búsqueda).
 */
final class RemoteEntityType extends AbstractType
{
    public function __construct(
        private readonly ManagerRegistry $registry,
        private readonly RouterInterface $router,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $manager = $this->registry->getManagerForClass($options['class']) ?? $this->registry->getManager();
        assert($manager instanceof ObjectManager);

        $builder->addViewTransformer(
            new RemoteEntityTransformer($manager, $options['class'], $options['text_property'], $options['primary_key'], $options['multiple']),
            true
        );
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['remote_path'] = $options['remote_path']
            ?: $this->router->generate($options['remote_route'], array_merge($options['remote_params'], ['page_limit' => $options['page_limit']]));

        foreach (['multiple', 'placeholder', 'primary_key', 'minimum_input_length', 'allow_clear', 'scroll', 'render_html', 'query_parameters', 'class_type'] as $name) {
            $view->vars[$name] = $options[$name];
        }

        if ($options['req_params'] !== []) {
            $accessor = PropertyAccess::createPropertyAccessor();
            $reqParams = [];
            foreach ($options['req_params'] as $key => $reqParam) {
                $reqParams[$key] = $accessor->getValue($view, $reqParam . '.vars[full_name]');
            }
            $view->vars['attr']['data-req_params'] = json_encode($reqParams);
        }

        if ($options['multiple']) {
            $view->vars['full_name'] .= '[]';
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'class' => null,
            'data_class' => null,
            'primary_key' => 'id',
            'text_property' => null,
            'remote_path' => null,
            'remote_route' => null,
            'remote_params' => [],
            'multiple' => false,
            'compound' => false,
            'minimum_input_length' => 1,
            'page_limit' => 10,
            'scroll' => false,
            'allow_clear' => false,
            'placeholder' => false,
            'required' => false,
            'req_params' => [],
            'class_type' => null,
            'query_parameters' => [],
            'render_html' => false,
        ]);
        $resolver->setRequired('class');
    }

    public function getBlockPrefix(): string
    {
        return 'remote_entity';
    }
}
