<?php
/*
  Copyright (C) 2018-2020: Luis Ramón López López

  This program is free software: you can redistribute it and/or modify
  it under the terms of the GNU Affero General Public License as published by
  the Free Software Foundation, either version 3 of the License, or
  (at your option) any later version.

  This program is distributed in the hope that it will be useful,
  but WITHOUT ANY WARRANTY; without even the implied warranty of
  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
  GNU Affero General Public License for more details.

  You should have received a copy of the GNU Affero General Public License
  along with this program.  If not, see [http://www.gnu.org/licenses/].
*/

namespace AppBundle\Security;

use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

abstract class CachedVoter extends Voter
{

    /**
     * @var CacheItemPoolInterface
     */
    private $cacheItemPool;

    public function __construct(CacheItemPoolInterface $cacheItemPool)
    {
        $this->cacheItemPool = $cacheItemPool;
    }

    /**
     * {@inheritdoc}
     */
    public function vote(TokenInterface $token, $subject, array $attributes)
    {
        $hash = $subject ? spl_object_hash($subject) . '_' : '';

        // abstain vote by default in case none of the attributes are supported
        $vote = self::ACCESS_ABSTAIN;

        foreach ($attributes as $attribute) {
            if (!$this->supports($attribute, $subject)) {
                continue;
            }

            // check cache
            $key = 'voter_' . $hash . $attribute;
            try {
                $cachedItem = $this->cacheItemPool->getItem($key);
            } catch (InvalidArgumentException $e) {
                $cachedItem = null;
            }
            if ($cachedItem->isHit()) {
                return $cachedItem->get();
            }

            // as soon as at least one attribute is supported, default is to deny access
            // grant access as soon as at least one attribute returns a positive response
            $vote = $this->voteOnAttribute($attribute, $subject, $token) ? self::ACCESS_GRANTED : self::ACCESS_DENIED;

            // write vote on cache
            if ($cachedItem) {
                $cachedItem->expiresAfter(1);
                $cachedItem->set($vote);
                $this->cacheItemPool->save($cachedItem);
            }
            if ($vote === self::ACCESS_GRANTED) {
                return $vote;
            }
        }
        return $vote;
    }
}
