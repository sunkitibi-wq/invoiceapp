<?php $__env->startComponent('mail::message'); ?>
# Invoice <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->number); ?>


Hello <?php echo new \Illuminate\Support\EncodedHtmlString($customer->name); ?>,

Please find invoice **<?php echo new \Illuminate\Support\EncodedHtmlString($invoice->number); ?>** attached, dated <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->issue_date->format('j M Y')); ?> and due <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->due_date->format('j M Y')); ?>.

<?php if (isset($component)) { $__componentOriginal91214b38020aa1d764d4a21e693f703c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal91214b38020aa1d764d4a21e693f703c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => $__env->getContainer()->make(Illuminate\View\Factory::class)->make('mail::panel'),'data' => []] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('mail::panel'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes([]); ?>
| | |
|:--|:--|
| **Total** | <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->total->format()); ?> |
| **Amount due** | <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->balance->format()); ?> |
| **Due date** | <?php echo new \Illuminate\Support\EncodedHtmlString($invoice->due_date->format('j M Y')); ?> |
 <?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $attributes = $__attributesOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__attributesOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal91214b38020aa1d764d4a21e693f703c)): ?>
<?php $component = $__componentOriginal91214b38020aa1d764d4a21e693f703c; ?>
<?php unset($__componentOriginal91214b38020aa1d764d4a21e693f703c); ?>
<?php endif; ?>

<?php if($invoice->notes): ?>
<?php echo new \Illuminate\Support\EncodedHtmlString($invoice->notes); ?>

<?php endif; ?>

<?php if($invoice->terms): ?>
<?php echo new \Illuminate\Support\EncodedHtmlString($invoice->terms); ?>

<?php endif; ?>

Thanks,<br>
<?php echo new \Illuminate\Support\EncodedHtmlString($from['name']); ?>

<?php echo $__env->renderComponent(); ?>
<?php /**PATH C:\Users\User\Herd\toma\resources\views/mail/invoice.blade.php ENDPATH**/ ?>