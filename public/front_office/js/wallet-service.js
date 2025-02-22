class WalletService {
    constructor() {
        this.web3 = null;
        this.accounts = [];
        
        // Listen for account changes from MetaMask
        if (typeof window.ethereum !== 'undefined') {
            window.ethereum.on('accountsChanged', (accounts) => {
                this.accounts = accounts;
                if (accounts.length === 0) {
                    // Handle disconnection from MetaMask
                    window.dispatchEvent(new CustomEvent('walletDisconnected'));
                } else {
                    window.dispatchEvent(new CustomEvent('walletAccountChanged', { 
                        detail: { account: accounts[0] } 
                    }));
                }
            });
        }
    }

    async connectMetaMask() {
        if (typeof window.ethereum === 'undefined') {
            throw new Error('MetaMask is not installed!');
        }

        try {
            // Request account access
            this.accounts = await window.ethereum.request({ method: 'eth_requestAccounts' });
            
            // Get the connected wallet address
            const walletAddress = this.accounts[0];
            
            // Get wallet balance
            const balance = await this.getBalance(walletAddress);

            return {
                address: walletAddress,
                network: await window.ethereum.request({ method: 'eth_chainId' }),
                balance: balance
            };
        } catch (error) {
            console.error('Error connecting to MetaMask:', error);
            throw error;
        }
    }

    async getBalance(address) {
        try {
            const balance = await window.ethereum.request({
                method: 'eth_getBalance',
                params: [address, 'latest']
            });
            
            // Convert balance from wei to ETH
            return this.weiToEth(balance);
        } catch (error) {
            console.error('Error getting balance:', error);
            throw error;
        }
    }

    weiToEth(weiBalance) {
        // Convert from wei (which is in hex) to ETH
        const wei = parseInt(weiBalance, 16);
        return wei / Math.pow(10, 18); // 1 ETH = 10^18 wei
    }

    isConnected() {
        return this.accounts.length > 0;
    }

    getCurrentAccount() {
        return this.accounts[0] || null;
    }
}

// Create global instance
window.walletService = new WalletService();