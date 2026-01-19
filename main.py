from fastapi import FastAPI, Request, HTTPException
from fastapi.responses import Response
from pydantic import BaseModel
import torch
from transformers import AutoModelForCausalLM, AutoTokenizer
import uvicorn
import os
import subprocess
import httpx
import asyncio
from contextlib import asynccontextmanager

# Global model variables
model = None
tokenizer = None
device = None

@asynccontextmanager
async def lifespan(app: FastAPI):
    global model, tokenizer, device
    model_path = "./Sheikh-ABF"
    print(f"Loading model from {model_path}...")
    tokenizer = AutoTokenizer.from_pretrained(model_path)
    model = AutoModelForCausalLM.from_pretrained(model_path)
    device = "cuda" if torch.cuda.is_available() else "cpu"
    model.to(device)
    model.eval()
    print(f"Model loaded on {device}")

    # Start PHP server internally on port 8001
    php_proc = subprocess.Popen(["php", "-S", "127.0.0.1:8001", "api.php"])

    yield

    php_proc.terminate()

app = FastAPI(lifespan=lifespan)

class GenerateRequest(BaseModel):
    prompt: str
    max_new_tokens: int = 50
    temperature: float = 0.7
    top_p: float = 0.95
    top_k: int = 50

@app.post("/generate_internal")
async def generate_internal(request: GenerateRequest):
    input_ids = tokenizer.encode(request.prompt, return_tensors='pt').to(device)
    with torch.no_grad():
        output_ids = model.generate(
            input_ids,
            max_new_tokens=request.max_new_tokens,
            do_sample=True,
            temperature=request.temperature,
            top_p=request.top_p,
            top_k=request.top_k,
            pad_token_id=tokenizer.pad_token_id,
            eos_token_id=tokenizer.eos_token_id
        )
    generated_text = tokenizer.decode(output_ids[0], skip_special_tokens=False)
    return {"generated_text": generated_text, "prompt": request.prompt}

@app.api_route("/{path_name:path}", methods=["GET", "POST", "PUT", "DELETE"])
async def proxy_to_php(request: Request, path_name: str):
    # Allow index, health, info, generate (which api.php handles)
    url = f"http://127.0.0.1:8001/{path_name}"

    async with httpx.AsyncClient() as client:
        method = request.method
        body = await request.body()
        headers = dict(request.headers)
        # Remove host to let client set it
        headers.pop("host", None)

        try:
            resp = await client.request(
                method,
                url,
                content=body,
                headers=headers,
                params=request.query_params,
                follow_redirects=True
            )
            return Response(
                content=resp.content,
                status_code=resp.status_code,
                headers=dict(resp.headers)
            )
        except Exception as e:
            # Fallback for root
            if path_name == "":
                 url = "http://127.0.0.1:8001/"
                 try:
                     resp = await client.get(url)
                     return Response(content=resp.content, status_code=resp.status_code, headers=dict(resp.headers))
                 except:
                     pass
            raise HTTPException(status_code=500, detail=f"Proxy error: {str(e)}")

if __name__ == "__main__":
    port = int(os.getenv("PORT", 8000))
    uvicorn.run(app, host="0.0.0.0", port=port)
