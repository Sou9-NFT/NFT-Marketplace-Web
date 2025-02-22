class WalletService {
    constructor() {
        this.web3 = null;
        this.accounts = [];
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
            
            // Listen for account changes
            window.ethereum.on('accountsChanged', (accounts) => {
                this.accounts = accounts;
                // Dispatch event for UI updates
                window.dispatchEvent(new CustomEvent('walletAccountChanged', { 
                    detail: { account: accounts[0] } 
                }));
            });

            return {
                address: walletAddress,
                network: await window.ethereum.request({ method: 'eth_chainId' })
            };
        } catch (error) {
            console.error('Error connecting to MetaMask:', error);
            throw error;
        }
    }

    async disconnectWallet() {
        this.accounts = [];
        // Dispatch disconnection event
        window.dispatchEvent(new CustomEvent('walletDisconnected'));
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