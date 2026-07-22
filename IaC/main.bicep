
// Construction Dashboard - Azure Infrastructure
// Deploys: VM, VNet, NSG, Public IP, Network Interface


// Parameters allow reuse across different deployments
@description('Name of the virtual machine')
param vmName string = 'construction-dashboard-vm'

@description('Admin username for the VM')
param adminUsername string = 'azureuser'

@description('SSH public key for VM authentication')
@secure()
param sshPublicKey string

@description('Azure region for all resources')
param location string = 'uksouth'

@description('VM size')
param vmSize string = 'Standard_B1s'

@description('Environment tag')
@allowed(['dev', 'staging', 'production'])
param environment string = 'production'


// Variables

var vnetName = '${vmName}-vnet'
var subnetName = '${vmName}-subnet'
var nsgName = '${vmName}-nsg'
var publicIPName = '${vmName}-pip'
var nicName = '${vmName}-nic'
var osDiskName = '${vmName}-osdisk'


// Network Security Group
// Restricts access to only required ports

resource nsg 'Microsoft.Network/networkSecurityGroups@2023-04-01' = {
  name: nsgName
  location: location
  tags: {
    environment: environment
    project: 'construction-dashboard'
  }
  properties: {
    securityRules: [
      {
        name: 'Allow-HTTP'
        properties: {
          priority: 100
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '80'
          description: 'Allow inbound HTTP traffic'
        }
      }
      {
        name: 'Allow-HTTPS'
        properties: {
          priority: 110
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: '*'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '443'
          description: 'Allow inbound HTTPS traffic'
        }
      }
      {
        name: 'Allow-SSH-Restricted'
        properties: {
          priority: 120
          protocol: 'Tcp'
          access: 'Allow'
          direction: 'Inbound'
          sourceAddressPrefix: 'YOUR_IP_ADDRESS/32'
          sourcePortRange: '*'
          destinationAddressPrefix: '*'
          destinationPortRange: '22'
          description: 'Restrict SSH to specific IP only - security best practice'
        }
      }
    ]
  }
}


// Virtual Network and Subnet

resource vnet 'Microsoft.Network/virtualNetworks@2023-04-01' = {
  name: vnetName
  location: location
  tags: {
    environment: environment
    project: 'construction-dashboard'
  }
  properties: {
    addressSpace: {
      addressPrefixes: ['10.0.0.0/16']
    }
    subnets: [
      {
        name: subnetName
        properties: {
          addressPrefix: '10.0.1.0/24'
          networkSecurityGroup: {
            id: nsg.id
          }
        }
      }
    ]
  }
}


// Public IP Address

resource publicIP 'Microsoft.Network/publicIPAddresses@2023-04-01' = {
  name: publicIPName
  location: location
  tags: {
    environment: environment
    project: 'construction-dashboard'
  }
  sku: {
    name: 'Basic'
  }
  properties: {
    publicIPAllocationMethod: 'Dynamic'
  }
}

// Network Interface Card

resource nic 'Microsoft.Network/networkInterfaces@2023-04-01' = {
  name: nicName
  location: location
  tags: {
    environment: environment
    project: 'construction-dashboard'
  }
  properties: {
    ipConfigurations: [
      {
        name: 'ipconfig1'
        properties: {
          subnet: {
            id: '${vnet.id}/subnets/${subnetName}'
          }
          publicIPAddress: {
            id: publicIP.id
          }
          privateIPAllocationMethod: 'Dynamic'
        }
      }
    ]
  }
}


// Virtual Machine - Ubuntu 22.04 LTS

resource vm 'Microsoft.Compute/virtualMachines@2023-07-01' = {
  name: vmName
  location: location
  tags: {
    environment: environment
    project: 'construction-dashboard'
  }
  properties: {
    hardwareProfile: {
      vmSize: vmSize
    }
    osProfile: {
      computerName: vmName
      adminUsername: adminUsername
      linuxConfiguration: {
        disablePasswordAuthentication: true
        ssh: {
          publicKeys: [
            {
              path: '/home/${adminUsername}/.ssh/authorized_keys'
              keyData: sshPublicKey
            }
          ]
        }
      }
    }
    storageProfile: {
      imageReference: {
        publisher: 'Canonical'
        offer: '0001-com-ubuntu-server-jammy'
        sku: '22_04-lts'
        version: 'latest'
      }
      osDisk: {
        name: osDiskName
        createOption: 'FromImage'
        managedDisk: {
          storageAccountType: 'Standard_LRS'
        }
      }
    }
    networkProfile: {
      networkInterfaces: [
        {
          id: nic.id
        }
      ]
    }
  }
}


// Resource Lock - prevents accidental deletion

resource vmLock 'Microsoft.Authorization/locks@2020-05-01' = {
  name: '${vmName}-delete-lock'
  scope: vm
  properties: {
    level: 'CanNotDelete'
    notes: 'Lock applied to prevent accidental deletion of the VM'
  }
}


// Outputs - useful after deployment

output vmName string = vm.name
output publicIPAddress string = publicIP.properties.ipAddress
output sshCommand string = 'ssh ${adminUsername}@${publicIP.properties.ipAddress}'
