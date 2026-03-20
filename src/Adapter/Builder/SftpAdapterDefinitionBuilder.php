<?php

declare (strict_types=1);
/*
 * This file is part of the flysystem-bundle project.
 *
 * (c) Titouan Galopin <galopintitouan@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */
namespace League\Flysystem_Bundle\Adapter\Builder;

use League\Flysystem\Phpseclib_V2\Sftp_Adapter as SftpAdapterLegacy;
use League\Flysystem\Phpseclib_V2\Sftp_Connection_Provider as SftpConnectionProviderLegacy;
use League\Flysystem\Phpseclib_V3\Sftp_Adapter;
use League\Flysystem\Phpseclib_V3\Sftp_Connection_Provider;
use League\Flysystem\Visibility;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Sftp_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'sftp';
    }
    protected function get_required_packages(): array
    {
        $adapter_fqcn = Sftp_Adapter::class;
        $package_require = 'league/flysystem-sftp-v3';
        // Prevent BC
        if (class_exists(Sftp_Adapter_Legacy::class)) {
            trigger_deprecation('league/flysystem-bundle', '2.2', '"league/flysystem-sftp" is deprecated, use "league/flysystem-sftp-v3" instead.');
            $adapter_fqcn = Sftp_Adapter_Legacy::class;
            $package_require = 'league/flysystem-sftp';
        }
        return [$adapter_fqcn => $package_require];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('host');
        $resolver->set_allowed_types('host', 'string');
        $resolver->set_required('username');
        $resolver->set_allowed_types('username', 'string');
        $resolver->set_default('password', null);
        $resolver->set_allowed_types('password', ['string', 'null']);
        $resolver->set_default('port', 22);
        $resolver->set_allowed_types('port', 'scalar');
        $resolver->set_default('root', '');
        $resolver->set_allowed_types('root', 'string');
        $resolver->set_default('privateKey', null);
        $resolver->set_allowed_types('privateKey', ['string', 'null']);
        $resolver->set_default('passphrase', null);
        $resolver->set_allowed_types('passphrase', ['string', 'null']);
        $resolver->set_default('hostFingerprint', null);
        $resolver->set_allowed_types('hostFingerprint', ['string', 'null']);
        $resolver->set_default('timeout', 90);
        $resolver->set_allowed_types('timeout', 'scalar');
        $resolver->set_default('directoryPerm', 0744);
        $resolver->set_allowed_types('directoryPerm', 'scalar');
        $resolver->set_default('permPrivate', 0700);
        $resolver->set_allowed_types('permPrivate', 'scalar');
        $resolver->set_default('permPublic', 0744);
        $resolver->set_allowed_types('permPublic', 'scalar');
        $resolver->set_default('connectivityChecker', null);
        $resolver->set_allowed_types('connectivityChecker', ['string', 'null']);
        $resolver->set_default('preferredAlgorithms', []);
        $resolver->set_allowed_types('preferredAlgorithms', 'array');
        $this->configure_unix_options($resolver);
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        // Prevent BC
        $adapter_fqcn = Sftp_Adapter::class;
        $connection_fqcn = Sftp_Connection_Provider::class;
        if (class_exists(Sftp_Adapter_Legacy::class)) {
            $adapter_fqcn = Sftp_Adapter_Legacy::class;
            $connection_fqcn = Sftp_Connection_Provider_Legacy::class;
        }
        if (null !== $options['connectivityChecker']) {
            $options['connectivityChecker'] = new Reference($options['connectivityChecker']);
        }
        $definition->set_class($adapter_fqcn);
        $definition->set_argument(0, (new Definition($connection_fqcn))->set_factory([$connection_fqcn, 'fromArray'])->add_argument($options)->set_shared(false));
        $definition->set_argument(1, $options['root']);
        $definition->set_argument(2, $this->create_unix_definition($options['permissions'], $default_visibility_for_directories ?? Visibility::PRIVATE));
    }
}