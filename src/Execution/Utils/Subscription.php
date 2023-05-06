<?php declare(strict_types=1);

namespace Nuwave\Lighthouse\Execution\Utils;

use Illuminate\Container\Container;
use Nuwave\Lighthouse\Schema\SchemaBuilder;
use Nuwave\Lighthouse\Subscriptions\Contracts\BroadcastsSubscriptions;
use Nuwave\Lighthouse\Subscriptions\Contracts\SubscriptionExceptionHandler;
use Nuwave\Lighthouse\Subscriptions\SubscriptionRegistry;

class Subscription
{
    /** Broadcast subscription to client(s). */
    public static function broadcast(string $subscriptionField, mixed $root, ?bool $shouldQueue = null): void
    {
        info('inside Subscription broadcast');
        
        // Ensure we have a schema and registered subscription fields
        // in the event we are calling this method in code.
        $schemaBuilder = Container::getInstance()->make(SchemaBuilder::class);
        $schemaBuilder->schema();
        
        info('we have a schema');

        $registry = Container::getInstance()->make(SubscriptionRegistry::class);
        if (! $registry->has($subscriptionField)) {
            throw new \InvalidArgumentException("No subscription field registered for {$subscriptionField}");
        }
        
        info('registry set up');

        // Default to the configuration setting if not specified
        if ($shouldQueue === null) {
            $shouldQueue = config('lighthouse.subscriptions.queue_broadcasts', false);
        }
        
        info(['shouldQueue', $shouldQueue]);
        info(['subscriptionField', $subscriptionField]);

        $subscription = $registry->subscription($subscriptionField);
        
        info('subscription set up');
        
        $broadcaster = Container::getInstance()->make(BroadcastsSubscriptions::class);
        
        info('broadcaster set up');

        try {
            info('inside try');
            if ($shouldQueue) {
                info('inside shouldQueue');
                $broadcaster->queueBroadcast($subscription, $subscriptionField, $root);
                info('queueBroadcast executed');
            } else {
                info('inside else');
                $broadcaster->broadcast($subscription, $subscriptionField, $root);
                info('broadcast executed');
            }
        } catch (\Throwable $throwable) {
            $exceptionHandler = Container::getInstance()->make(SubscriptionExceptionHandler::class);
            $exceptionHandler->handleBroadcastError($throwable);
        }
    }
}
