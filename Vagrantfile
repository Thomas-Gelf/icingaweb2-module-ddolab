# -*- mode: ruby -*-
# vi: set ft=ruby :

# Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+

Vagrant.require_version ">= 1.5.0"

Vagrant.configure(2) do |config|
  config.vm.box = "bento/ubuntu-16.04"

  config.vm.provision :shell, :path => "vagrant/provision.sh"
end
