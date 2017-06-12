# requires:
# - https://atlas.hashicorp.com/bento/boxes/ubuntu-16.04
# - https://github.com/vagrant-landrush/landrush
# - https://github.com/aidanns/vagrant-reload

# change this to your project's name
VIRTUALBOX_DISPLAY_NAME = 'php-threema'

Vagrant.configure(2) do |config|
    config.vm.box = 'bento/ubuntu-16.04'
    config.vm.hostname = 'phpstorm.box'
    config.ssh.insert_key = false
    config.ssh.shell = "bash -c 'BASH_ENV=/etc/profile exec bash'"
    config.ssh.forward_agent = true

    config.vm.provider :virtualbox do |vb|
        vb.name = VIRTUALBOX_DISPLAY_NAME

        vb.customize ["modifyvm", :id, "--cpus", "1"]
        vb.customize ['modifyvm', :id, '--memory', '2048']
        vb.customize ['modifyvm', :id, '--natdnsproxy1', 'on']
        vb.customize ['modifyvm', :id, '--natdnshostresolver1', 'on']
        vb.customize ["modifyvm", :id, "--ostype", "Ubuntu_64"]

        # Display the VirtualBox GUI when booting the machine
#        vb.gui = true
#        vb.customize ["modifyvm", :id, "--clipboard", "bidirectional"]
    end

    config.vm.synced_folder './', '/vagrant'
    config.vm.network "private_network", ip: "192.168.10.10"
    config.vm.network 'forwarded_port', guest: 80, host: 8000, auto_correct: true

    config.landrush.enabled = true
	config.landrush.tld = 'box'
    config.landrush.host 'phpstorm.box'

    config.vm.provision 'shell', path: './scripts/update.sh'
    config.vm.provision :reload
    config.vm.provision 'shell', path: './scripts/install-amp.sh'
    config.vm.provision :reload
end
