// node/issueCertificate.js
require("dotenv").config();
const { ethers } = require("ethers");
const contractABI = require("./abi.json");
const contractAddress = process.env.CERT_CONTRACT_ADDRESS;

async function main() {
    const [certHash, metadataURI] = process.argv.slice(2);
    const provider = new ethers.JsonRpcProvider(process.env.RPC_URL);
    const wallet = new ethers.Wallet(process.env.PRIVATE_KEY, provider);
    const contract = new ethers.Contract(contractAddress, contractABI, wallet);

    const tx = await contract.issueCertificate(certHash, metadataURI);
    console.log(tx.hash);          // <<–– prints the transaction hash
    // we no longer need to wait for events here, unless you still want certId
}

main().catch(err => {
    console.error("❌ Blockchain Error:", err.message);
    process.exit(1);
});