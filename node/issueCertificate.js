// node/issueCertificate.js
const path = require('path');
require('dotenv').config({ path: path.resolve(__dirname, '../.env'), debug: false });
const { ethers } = require('ethers');
const abi = require('./abi.json');

async function main() {
    const [certHash, metadataURI] = process.argv.slice(2);
    if (!certHash || !metadataURI) {
        console.error('Usage: node issueCertificate.js <certHash> <metadataURI>');
        process.exit(1);
    }
    const provider = new ethers.JsonRpcProvider(process.env.RPC_URL);
    const wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
    const contract = new ethers.Contract(process.env.CERT_CONTRACT_ADDRESS, abi, wallet);

    const tx = await contract.issueCertificate(certHash, metadataURI);
    const receipt = await tx.wait();
    const evt = receipt.logs
        .map(l => { try { return contract.interface.parseLog(l); } catch { return null } })
        .filter(e => e && e.name === 'CertificateIssued')[0];

    console.log(JSON.stringify({
        certId: evt.args.certId.toString(),
        txHash: tx.hash
    }));
}

main().catch(err => {
    console.error('Blockchain Error:', err.message);
    process.exit(1);
});
