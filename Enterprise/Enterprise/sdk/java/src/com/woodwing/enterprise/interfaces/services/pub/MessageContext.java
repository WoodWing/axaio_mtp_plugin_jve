/**
 * MessageContext.java
 *
 * This file was auto-generated from WSDL
 * by the Apache Axis 1.4 Apr 22, 2006 (06:55:48 PDT) WSDL2Java emitter.
 */

package com.woodwing.enterprise.interfaces.services.pub;

public class MessageContext  implements java.io.Serializable {
    private com.woodwing.enterprise.interfaces.services.pub.ObjectInfo[] objects;

    private com.woodwing.enterprise.interfaces.services.pub.PageInfo page;

    public MessageContext() {
    }

    public MessageContext(
           com.woodwing.enterprise.interfaces.services.pub.ObjectInfo[] objects,
           com.woodwing.enterprise.interfaces.services.pub.PageInfo page) {
           this.objects = objects;
           this.page = page;
    }


    /**
     * Gets the objects value for this MessageContext.
     * 
     * @return objects
     */
    public com.woodwing.enterprise.interfaces.services.pub.ObjectInfo[] getObjects() {
        return objects;
    }


    /**
     * Sets the objects value for this MessageContext.
     * 
     * @param objects
     */
    public void setObjects(com.woodwing.enterprise.interfaces.services.pub.ObjectInfo[] objects) {
        this.objects = objects;
    }


    /**
     * Gets the page value for this MessageContext.
     * 
     * @return page
     */
    public com.woodwing.enterprise.interfaces.services.pub.PageInfo getPage() {
        return page;
    }


    /**
     * Sets the page value for this MessageContext.
     * 
     * @param page
     */
    public void setPage(com.woodwing.enterprise.interfaces.services.pub.PageInfo page) {
        this.page = page;
    }

    private java.lang.Object __equalsCalc = null;
    public synchronized boolean equals(java.lang.Object obj) {
        if (!(obj instanceof MessageContext)) return false;
        MessageContext other = (MessageContext) obj;
        if (obj == null) return false;
        if (this == obj) return true;
        if (__equalsCalc != null) {
            return (__equalsCalc == obj);
        }
        __equalsCalc = obj;
        boolean _equals;
        _equals = true && 
            ((this.objects==null && other.getObjects()==null) || 
             (this.objects!=null &&
              java.util.Arrays.equals(this.objects, other.getObjects()))) &&
            ((this.page==null && other.getPage()==null) || 
             (this.page!=null &&
              this.page.equals(other.getPage())));
        __equalsCalc = null;
        return _equals;
    }

    private boolean __hashCodeCalc = false;
    public synchronized int hashCode() {
        if (__hashCodeCalc) {
            return 0;
        }
        __hashCodeCalc = true;
        int _hashCode = 1;
        if (getObjects() != null) {
            for (int i=0;
                 i<java.lang.reflect.Array.getLength(getObjects());
                 i++) {
                java.lang.Object obj = java.lang.reflect.Array.get(getObjects(), i);
                if (obj != null &&
                    !obj.getClass().isArray()) {
                    _hashCode += obj.hashCode();
                }
            }
        }
        if (getPage() != null) {
            _hashCode += getPage().hashCode();
        }
        __hashCodeCalc = false;
        return _hashCode;
    }

    // Type metadata
    private static org.apache.axis.description.TypeDesc typeDesc =
        new org.apache.axis.description.TypeDesc(MessageContext.class, true);

    static {
        typeDesc.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "MessageContext"));
        org.apache.axis.description.ElementDesc elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("objects");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Objects"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "ObjectInfo"));
        elemField.setNillable(false);
        elemField.setItemQName(new javax.xml.namespace.QName("", "ObjectInfo"));
        typeDesc.addFieldDesc(elemField);
        elemField = new org.apache.axis.description.ElementDesc();
        elemField.setFieldName("page");
        elemField.setXmlName(new javax.xml.namespace.QName("", "Page"));
        elemField.setXmlType(new javax.xml.namespace.QName("urn:EnterprisePublishing", "PageInfo"));
        elemField.setNillable(true);
        typeDesc.addFieldDesc(elemField);
    }

    /**
     * Return type metadata object
     */
    public static org.apache.axis.description.TypeDesc getTypeDesc() {
        return typeDesc;
    }

    /**
     * Get Custom Serializer
     */
    public static org.apache.axis.encoding.Serializer getSerializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanSerializer(
            _javaType, _xmlType, typeDesc);
    }

    /**
     * Get Custom Deserializer
     */
    public static org.apache.axis.encoding.Deserializer getDeserializer(
           java.lang.String mechType, 
           java.lang.Class _javaType,  
           javax.xml.namespace.QName _xmlType) {
        return 
          new  org.apache.axis.encoding.ser.BeanDeserializer(
            _javaType, _xmlType, typeDesc);
    }

}
