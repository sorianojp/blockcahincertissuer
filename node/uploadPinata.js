// node/uploadPinata.js
require('dotenv').config({ path: require('path').resolve(__dirname, '../.env'), debug: false });
const axios = require('axios'), fs = require('fs'), FormData = require('form-data'), path = require('path');

async function main() {
    const filePath = process.argv[2];
    const jwt = process.env.PINATA_JWT;
    const form = new FormData();
    form.append('file', fs.createReadStream(path.resolve(filePath)));
    const res = await axios.post('https://api.pinata.cloud/pinning/pinFileToIPFS', form, {
        maxBodyLength: Infinity,
        headers: { Authorization: `Bearer ${jwt}`, ...form.getHeaders() }
    });
    console.log('ipfs://' + res.data.IpfsHash);
}

main().catch(e => {
    console.error('Upload failed:', e.message);
    process.exit(1);
});
