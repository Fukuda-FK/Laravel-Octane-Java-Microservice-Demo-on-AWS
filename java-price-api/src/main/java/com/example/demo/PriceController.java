package com.example.demo;

import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.RestController;
import java.util.Map;
import java.util.HashMap;

@RestController
public class PriceController {

    @GetMapping("/price/{productId}")
    public Map<String, Object> getPrice(@PathVariable String productId) {
        Map<String, Object> response = new HashMap<>();
        response.put("productId", productId);
        response.put("price", 1980);
        response.put("source", "Java API");
        
        // 環境変数 'SERVER_HOSTNAME' からホスト名を取得します
        String serverHostname = System.getenv("SERVER_HOSTNAME");
        response.put("server_hostname", serverHostname != null ? serverHostname : "N/A");
        
        return response;
    }
}