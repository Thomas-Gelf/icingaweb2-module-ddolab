# -*- mode: ruby -*-
# vi: set ft=ruby :

# Icinga Web 2 | (c) 2017 Icinga Development Team | GPLv2+

Vagrant.require_version ">= 1.5.0"

Vagrant.configure(2) do |config|
  config.vm.network "forwarded_port", guest: 80, host: 8080,
    auto_correct: true
  config.vm.network "forwarded_port", guest: 6379, host: 6379,
    auto_correct: true

  config.vm.box = "bento/ubuntu-16.04"

  config.vm.provision :shell, :path => "vagrant/provision.sh"

  config.vm.provider :parallels do |p, override|
    p.name = "Ddolab"
  end
end
