<?php
/* For licensing terms, see /license.txt */

namespace Chamilo\ApiBundle\GraphQL\Resolver;

use Chamilo\CoreBundle\Entity\Course;
use Chamilo\CoreBundle\Framework\Container;
use Chamilo\CoreBundle\Repository\MessageRepository;
use Chamilo\UserBundle\Entity\User;
use GraphQL\Error\UserError;
use Overblog\GraphQLBundle\Definition\Resolver\AliasedInterface;
use Overblog\GraphQLBundle\Definition\Resolver\ResolverInterface;

/**
 * Class UserResolver.
 *
 * @package Chamilo\ApiBundle\GraphQL\Resolver
 */
class UserResolver implements ResolverInterface, AliasedInterface
{
    public const IMAGE_SIZE_TINY = 16;
    public const IMAGE_SIZE_SMALL = 32;
    public const IMAGE_SIZE_MEDIUM = 64;
    public const IMAGE_SIZE_BIG = 128;

    /**
     * Returns methods aliases.
     *
     * For instance:
     * array('myMethod' => 'myAlias')
     *
     * @return array
     */
    public static function getAliases(): array
    {
        return [
            'resolveUserPicture' => 'user_picture',
            'resolveUserMessages' => 'user_messages',
            'resolveCourses' => 'user_courses',
        ];
    }

    /**
     * @param User $user
     * @param int  $size
     *
     * @return string
     */
    public function resolveUserPicture(User $user, $size): string
    {
        $path = $user->getAvatarOrAnonymous((int) $size);
        $url = Container::getAsset()->getUrl($path);

        return $url;
    }

    /**
     * @param User         $user
     * @param int          $lastId
     * @param \ArrayObject $context
     *
     * @return array
     */
    public function resolveUserMessages(User $user, $lastId = 0, \ArrayObject $context): array
    {
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($user->getId() !== $contextUser->getId()) {
            throw new UserError(get_lang('UserInfoDoesNotMatch'));
        }

        /** @var MessageRepository $messageRepo */
        $messageRepo = Container::getEntityManager()->getRepository('ChamiloCoreBundle:Message');
        $messages = $messageRepo->getFromLastOneReceived($user, (int) $lastId);

        return $messages;
    }

    /**
     * @param User         $user
     * @param \ArrayObject $context
     *
     * @return array
     * @throws \Doctrine\ORM\ORMException
     * @throws \Doctrine\ORM\OptimisticLockException
     * @throws \Doctrine\ORM\TransactionRequiredException
     */
    public function resolveCourses(User $user, \ArrayObject $context)
    {
        /** @var User $contextUser */
        $contextUser = $context['user'];

        if ($user->getId() !== $contextUser->getId()) {
            throw new UserError(get_lang('UserInfoDoesNotMatch'));
        }

        $courses = [];
        $coursesInfo = \CourseManager::get_courses_list_by_user_id($user->getId());
        $em = Container::getEntityManager();

        foreach ($coursesInfo as $courseInfo) {
            /** @var Course $course */
            $course = $em->find('ChamiloCoreBundle:Course', $courseInfo['real_id']);

            if ($course) {
                $courses[] = $course;
            }
        }

        return $courses;
    }
}