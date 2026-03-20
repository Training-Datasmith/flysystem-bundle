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

use League\Flysystem\Ftp\Ftp_Adapter;
use League\Flysystem\Ftp\Ftp_Connection_Options;
use League\Flysystem\Visibility;
use Symfony\Component\Dependency_Injection\Definition;
use Symfony\Component\Dependency_Injection\Reference;
use Symfony\Component\Options_Resolver\Options_Resolver;
/**
 * @author Titouan Galopin <galopintitouan@gmail.com>
 *
 * @internal
 */
final class Ftp_Adapter_Definition_Builder extends Abstract_Adapter_Definition_Builder
{
    public function get_name(): string
    {
        return 'ftp';
    }
    protected function get_required_packages(): array
    {
        return [Ftp_Adapter::class => 'league/flysystem-ftp'];
    }
    protected function configure_options(Options_Resolver $resolver): void
    {
        $resolver->set_required('host');
        $resolver->set_allowed_types('host', 'string');
        $resolver->set_required('username');
        $resolver->set_allowed_types('username', 'string');
        $resolver->set_required('password');
        $resolver->set_allowed_types('password', 'string');
        $resolver->set_default('port', 21);
        $resolver->set_allowed_types('port', 'scalar');
        $resolver->set_default('root', '');
        $resolver->set_allowed_types('root', 'string');
        $resolver->set_default('passive', true);
        $resolver->set_allowed_types('passive', 'scalar');
        $resolver->set_default('ssl', false);
        $resolver->set_allowed_types('ssl', 'scalar');
        $resolver->set_default('timeout', 90);
        $resolver->set_allowed_types('timeout', 'scalar');
        $resolver->set_default('ignore_passive_address', null);
        $resolver->set_allowed_types('ignore_passive_address', ['null', 'bool', 'scalar']);
        $resolver->set_default('utf8', false);
        $resolver->set_allowed_types('utf8', 'scalar');
        $resolver->set_default('transfer_mode', null);
        $resolver->set_allowed_types('transfer_mode', ['null', 'scalar']);
        $resolver->set_allowed_values('transfer_mode', [null, FTP_ASCII, FTP_BINARY]);
        $resolver->set_default('system_type', null);
        $resolver->set_allowed_types('system_type', ['null', 'string']);
        $resolver->set_allowed_values('system_type', [null, 'windows', 'unix']);
        $resolver->set_default('timestamps_on_unix_listings_enabled', false);
        $resolver->set_allowed_types('timestamps_on_unix_listings_enabled', 'bool');
        $resolver->set_default('recurse_manually', true);
        $resolver->set_allowed_types('recurse_manually', 'bool');
        $resolver->set_default('connectivityChecker', null);
        $resolver->set_allowed_types('connectivityChecker', ['string', 'null']);
        $this->configure_unix_options($resolver);
    }
    protected function configure_definition(Definition $definition, array $options, ?string $default_visibility_for_directories): void
    {
        $options['transferMode'] = $options['transfer_mode'];
        $options['systemType'] = $options['system_type'];
        $options['timestampsOnUnixListingsEnabled'] = $options['timestamps_on_unix_listings_enabled'];
        $options['ignorePassiveAddress'] = $options['ignore_passive_address'];
        $options['recurseManually'] = $options['recurse_manually'];
        $connectivity_checker = null;
        if (null !== $options['connectivityChecker']) {
            $connectivity_checker = new Reference($options['connectivityChecker']);
        }
        unset($options['transfer_mode'], $options['system_type'], $options['timestamps_on_unix_listings_enabled'], $options['ignore_passive_address'], $options['recurse_manually'], $options['connectivityChecker']);
        $definition->set_class(Ftp_Adapter::class);
        $definition->set_argument(0, (new Definition(Ftp_Connection_Options::class))->set_factory([Ftp_Connection_Options::class, 'fromArray'])->add_argument($options)->set_shared(false));
        $definition->set_argument(1, null);
        $definition->set_argument(2, $connectivity_checker);
        $definition->set_argument(3, $this->create_unix_definition($options['permissions'], $default_visibility_for_directories ?? Visibility::PRIVATE));
    }
}